<?php

namespace App\Services;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EditAppointment
{
    public function update(MasterProfile $master, MasterAppointment $appointment, array $data, int $userId): MasterAppointment
    {
        $notify = false;
        $result = DB::transaction(function () use ($master, $appointment, $data, $userId, &$notify) {
            $lockedMaster = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            $a = MasterAppointment::whereKey($appointment->id)->where('master_profile_id', $lockedMaster->id)->lockForUpdate()->firstOrFail();
            if (! in_array($a->status, ['pending', 'confirmed'])) {
                throw ValidationException::withMessages(['booking' => 'Отменённую или завершённую запись редактировать нельзя.']);
            }
            if ((int) $a->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['booking' => 'Запись уже изменилась в другом окне. Обновите страницу перед редактированием.']);
            }
            $local = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at_local'], $lockedMaster->timezone);
            if ($local->format('Y-m-d\TH:i') !== $data['starts_at_local']) {
                throw ValidationException::withMessages(['starts_at_local' => 'Это местное время недоступно в часовом поясе мастера.']);
            }
            $start = $local->utc();
            $end = $start->addMinutes($data['duration_minutes']);
            if ($start->lte(CarbonImmutable::now('UTC')) && ! $start->equalTo($a->starts_at)) {
                throw ValidationException::withMessages(['starts_at_local' => 'Перенести запись можно только на будущее время.']);
            }
            if ($start->gt(CarbonImmutable::now('UTC')->addYears(5))) {
                throw ValidationException::withMessages(['starts_at_local' => 'Выберите дату в пределах пяти лет.']);
            }
            $timeChanged = ! $start->equalTo($a->starts_at) || ! $end->equalTo($a->ends_at);
            if ($timeChanged) {
                if (! (new WorkingHours)->contains($lockedMaster, $start, $end)) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Весь интервал должен попадать в рабочее расписание. Сначала измените расписание, если мастер готов принять в другое время.']);
                }
                $busy = $lockedMaster->appointments()->whereKeyNot($a->id)->whereIn('status', ['pending', 'confirmed'])
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $blocked = DB::table('master_time_blocks')->where('master_profile_id', $lockedMaster->id)
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $groupBusy = DB::table('master_group_sessions')->where('master_profile_id', $lockedMaster->id)
                    ->where('status', 'open')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                if ($busy || $blocked || $groupBusy) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Интервал пересекается с другой записью или закрытым временем. Выберите другое время или длительность.']);
                }
            }
            $original = collect($a->services_snapshot ?: ($a->master_service_id ? [[
                'id' => $a->master_service_id, 'name' => $a->service_name_snapshot,
                'duration_minutes' => (int) $a->starts_at->diffInMinutes($a->ends_at), 'price_som' => $a->price_som,
            ]] : []))->keyBy('id');
            $available = $lockedMaster->services()->where('is_active', true)->whereIn('id', $data['service_ids'])->get()->keyBy('id');
            $snapshots = [];
            foreach ($data['service_ids'] as $id) {
                if ($original->has($id)) {
                    $snapshots[] = $original->get($id);
                } elseif ($available->has($id)) {
                    $snapshots[] = $available->get($id)->only('id', 'name', 'duration_minutes', 'price_som');
                } else {
                    throw ValidationException::withMessages(['service_ids' => 'Одна из услуг недоступна или принадлежит другому мастеру.']);
                }
            }
            $snapshot = fn ($value) => [
                'starts_at' => $value->starts_at->toIso8601String(), 'ends_at' => $value->ends_at->toIso8601String(),
                'duration_minutes' => (int) $value->starts_at->diffInMinutes($value->ends_at),
                'price_som' => (int) $value->price_som, 'services' => $value->services_snapshot,
                'service_name' => $value->service_name_snapshot, 'client_name' => $value->client_name,
                'client_phone' => $value->client_phone, 'client_comment' => $value->client_comment,
            ];
            $before = $snapshot($a);
            $phoneChanged = WhatsApp::phone($a->client_phone) !== WhatsApp::phone($data['client_phone']);
            $a->fill([
                'starts_at' => $start, 'ends_at' => $end, 'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'], 'client_comment' => $data['client_comment'] ?? null,
                'price_som' => $data['price_som'], 'services_snapshot' => $snapshots,
                'service_name_snapshot' => mb_substr(collect($snapshots)->pluck('name')->implode(' + '), 0, 255),
                'master_service_id' => count($snapshots) === 1 && $available->has($snapshots[0]['id']) ? $snapshots[0]['id'] : null,
            ]);
            $after = $snapshot($a);
            if ($before === $after) {
                return $a;
            }
            $notify = collect($before)->except('client_comment')->all() !== collect($after)->except('client_comment')->all();
            if ($phoneChanged) {
                // Do not leave a visit linked to the previous customer's
                // private CRM history when the master corrects the phone.
                $digits = preg_replace('/\\D+/', '', $data['client_phone']);
                $client = DB::table('master_clients')
                    ->where('master_profile_id', $lockedMaster->id)
                    ->where('phone_digits', $digits)->first(['id']);
                $a->master_client_id = $client?->id ?? DB::table('master_clients')->insertGetId([
                    'master_profile_id' => $lockedMaster->id,
                    'phone_digits' => $digits,
                    'name' => $data['client_name'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $a->whatsapp_consent = false;
            }
            $a->revision++;
            if ($notify) {
                $a->notification_revision++;
            }
            $a->save();
            $a->changes()->create(['user_id' => $userId, 'before' => $before, 'after' => $after, 'reason' => $data['reason'] ?? null]);

            return $a;
        }, 3);
        if ($notify) {
            app(WhatsApp::class)->confirmed($result);
        }

        return $result;
    }
}
