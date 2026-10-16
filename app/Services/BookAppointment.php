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
        MasterService $service,
        CarbonImmutable $startsAtUtc,
        string $clientName,
        string $clientPhone,
        ?string $comment = null
    ): MasterAppointment {
        if ($service->master_profile_id !== $master->id || ! $service->is_active) {
            throw ValidationException::withMessages(['service' => 'Услуга недоступна.']);
        }

        if ($startsAtUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages(['starts_at' => 'Выберите будущее время.']);
        }

        return DB::transaction(function () use ($master, $service, $startsAtUtc, $clientName, $clientPhone, $comment) {
            $lockedMaster = MasterProfile::query()->whereKey($master->id)->lockForUpdate()->firstOrFail();

            $service = MasterService::query()
                ->whereKey($service->id)
                ->where('master_profile_id', $lockedMaster->id)
                ->where('is_active', true)
                ->firstOrFail();

            if (! $lockedMaster->booking_enabled) {
                throw ValidationException::withMessages(['booking' => 'Онлайн-запись отключена.']);
            }

            $endsAtUtc = $startsAtUtc->addMinutes($service->duration_minutes);
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

            if ($overlap || $blocked) {
                throw ValidationException::withMessages(['starts_at' => 'Это время уже занято.']);
            }

            if ($startsAtUtc->lessThanOrEqualTo(CarbonImmutable::now('UTC')) ||
                $service->duration_minutes < 5 ||
                ! (new WorkingHours)->contains($lockedMaster, $startsAtUtc, $endsAtUtc)) {
                throw ValidationException::withMessages(['starts_at' => 'Выберите время в рабочем расписании мастера.']);
            }

            return MasterAppointment::query()->create([
                'master_profile_id' => $lockedMaster->id,
                'master_service_id' => $service->id,
                'client_name' => $clientName,
                'client_phone' => $clientPhone,
                'client_comment' => $comment,
                'starts_at' => $startsAtUtc->format('Y-m-d H:i:s'),
                'ends_at' => $endsAtUtc->format('Y-m-d H:i:s'),
                'price_som' => $service->price_som,
                'service_name_snapshot' => $service->name,
                'status' => $lockedMaster->auto_confirm ? 'confirmed' : 'pending',
            ]);
        }, 3);
    }
}
