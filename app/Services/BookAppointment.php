<?php

namespace App\Services;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\MasterService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BookAppointment
{
    /**
     * All stored timestamps are UTC. The master profile row is locked to
     * serialize bookings for a single master even when the slot is still empty.
     */
    public function create(
        MasterProfile $master,
        MasterService|array $service,
        CarbonImmutable $startsAtUtc,
        string $clientName,
        string $clientPhone,
        ?string $comment = null,
        bool $whatsappConsent = false,
        bool $manual = false
    ): MasterAppointment {
        $selected = is_array($service) ? $service : [$service];
        if (count($selected) < 1 || count($selected) > 20 ||
            collect($selected)->contains(fn ($item) => ! $item instanceof MasterService || $item->master_profile_id !== $master->id || ! $item->is_active)) {
            throw ValidationException::withMessages(['service_ids' => 'Выберите доступные услуги мастера.']);
        }
        $ids = collect($selected)->pluck('id')->unique()->values();
        if ($ids->count() !== count($selected)) {
            throw ValidationException::withMessages(['service_ids' => 'Услуги не должны повторяться.']);
        }

        if ($startsAtUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages(['starts_at' => 'Выберите будущее время.']);
        }

        $appointment = DB::transaction(function () use ($master, $ids, $startsAtUtc, $clientName, $clientPhone, $comment, $whatsappConsent, $manual) {
            $lockedMaster = MasterProfile::query()->whereKey($master->id)->lockForUpdate()->firstOrFail();

            $services = MasterService::query()
                ->whereIn('id', $ids)
                ->where('master_profile_id', $lockedMaster->id)
                ->where('is_active', true)
                ->orderBy('sort_order')->orderBy('id')->get();
            if ($services->count() !== $ids->count()) {
                throw ValidationException::withMessages(['service_ids' => 'Одна из услуг больше недоступна.']);
            }
            $duration = $services->sum('duration_minutes');

            if ($lockedMaster->is_demo || (! $manual && ! $lockedMaster->booking_enabled)) {
                throw ValidationException::withMessages(['booking' => 'Онлайн-запись отключена.']);
            }

            $endsAtUtc = $startsAtUtc->addMinutes($duration);
            $overlap = MasterAppointment::query()
                ->where('master_profile_id', $lockedMaster->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $endsAtUtc->format('Y-m-d H:i:s'))
                ->where('ends_at', '>', $startsAtUtc->format('Y-m-d H:i:s'))
                ->exists();

            $blocked = DB::table('master_time_blocks')
                ->where('master_profile_id', $lockedMaster->id)
                ->where('starts_at', '<', $endsAtUtc->format('Y-m-d H:i:s'))
                ->where('ends_at', '>', $startsAtUtc->format('Y-m-d H:i:s'))
                ->exists();

            $groupBusy = DB::table('master_group_sessions')
                ->where('master_profile_id', $lockedMaster->id)->where('status', 'open')
                ->where('starts_at', '<', $endsAtUtc->format('Y-m-d H:i:s'))
                ->where('ends_at', '>', $startsAtUtc->format('Y-m-d H:i:s'))->exists();
            if ($overlap || $blocked || $groupBusy) {
                throw ValidationException::withMessages(['starts_at' => 'Это время уже занято.']);
            }

            if ($startsAtUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC')) ||
                $services->contains(fn ($item) => $item->duration_minutes < 5) ||
                ! (new WorkingHours)->contains($lockedMaster, $startsAtUtc, $endsAtUtc)) {
                throw ValidationException::withMessages(['starts_at' => 'Выберите время в рабочем расписании мастера.']);
            }

            // A customer can book without creating an account. Phone identity is
            // scoped to this master; no customer data is shared across businesses.
            $phoneDigits = WhatsApp::phone($clientPhone) ?? preg_replace('/\\D+/', '', $clientPhone);
            $client = DB::table('master_clients')
                ->where('master_profile_id', $lockedMaster->id)
                ->where('phone_digits', $phoneDigits)
                ->first();
            if ($client) {
                $clientId = $client->id;
                DB::table('master_clients')->where('id', $clientId)->update([
                    'name' => $clientName, 'archived_at' => null, 'updated_at' => now(),
                ]);
            } else {
                $clientId = DB::table('master_clients')->insertGetId([
                    'master_profile_id' => $lockedMaster->id,
                    'phone_digits' => $phoneDigits,
                    'name' => $clientName,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return MasterAppointment::query()->create([
                'master_profile_id' => $lockedMaster->id,
                'master_client_id' => $clientId,
                'master_service_id' => $services->count() === 1 ? $services->first()->id : null,
                'services_snapshot' => $services->map->only(['id', 'name', 'duration_minutes', 'price_som'])->all(),
                'client_name' => $clientName,
                'client_phone' => $clientPhone,
                'client_comment' => $comment,
                'whatsapp_consent' => $whatsappConsent,
                'starts_at' => $startsAtUtc->format('Y-m-d H:i:s'),
                'ends_at' => $endsAtUtc->format('Y-m-d H:i:s'),
                'price_som' => $services->sum('price_som'),
                'service_name_snapshot' => mb_substr($services->pluck('name')->implode(' + '), 0, 255),
                'status' => ($manual || $lockedMaster->auto_confirm) ? 'confirmed' : 'pending',
                'attendance_status' => 'expected',
                'payment_status' => null,
            ]);
        }, 3);
        if (! $manual) {
            app(WhatsApp::class)->confirmed($appointment);
        }

        return $appointment;
    }
}
