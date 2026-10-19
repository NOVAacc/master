<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\WorkingHours;
use App\Services\ReserveGroupSeat;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GroupSessionController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'starts_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'capacity' => ['required', 'integer', 'between:2,100'],
        ]);
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        DB::transaction(function () use ($master, $data) {
            $locked = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            if (! $locked->booking_enabled || $locked->is_demo) {
                throw ValidationException::withMessages(['booking' => 'Приём записей отключён.']);
            }
            $service = $locked->services()->whereKey($data['service_id'])->where('is_active', true)->firstOrFail();
            $local = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at_local'], $locked->timezone);
            if (! $local || $local->format('Y-m-d\\TH:i') !== $data['starts_at_local']) {
                throw ValidationException::withMessages(['starts_at_local' => 'Проверьте дату и время.']);
            }
            $start = $local->utc();
            $end = $start->addMinutes($service->duration_minutes);
            if ($start->lessThanOrEqualTo(CarbonImmutable::now('UTC'))
                || ! (new WorkingHours)->contains($locked, $start, $end)) {
                throw ValidationException::withMessages(['starts_at_local' => 'Время вне рабочего расписания.']);
            }
            if ($locked->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()
                || DB::table('master_time_blocks')->where('master_profile_id', $locked->id)
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()
                || DB::table('master_group_sessions')->where('master_profile_id', $locked->id)
                    ->where('status', 'open')->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at_local' => 'Интервал занят.']);
            }
            DB::table('master_group_sessions')->insert([
                'master_profile_id' => $locked->id,
                'master_service_id' => $service->id,
                'starts_at' => $start->format('Y-m-d H:i:s'),
                'ends_at' => $end->format('Y-m-d H:i:s'),
                'capacity' => $data['capacity'],
                'reserved_seats' => 0,
                'service_name_snapshot' => $service->name,
                'price_som' => $service->price_som,
                'status' => 'open',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);

        return back()->with('success', 'Групповое занятие создано.');
    }

    public function register(Request $request, int $session, ReserveGroupSeat $booking)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'regex:/^\\+?[0-9 ()-]{7,30}$/'],
            'consent' => ['accepted'],
        ]);
        $booking->reserve($session, $data['client_name'], $data['client_phone']);

        return back()->with('success', 'Место забронировано.');
    }

    public function cancelParticipant(Request $request, int $session, int $participant)
    {
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();

        DB::transaction(function () use ($master, $session, $participant) {
            // Respect the master -> session -> participant lock ordering used
            // for new registrations, so the final available seat is safe.
            $lockedMaster = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            $row = DB::table('master_group_sessions')
                ->where('master_profile_id', $lockedMaster->id)
                ->where('id', $session)->lockForUpdate()->firstOrFail();
            $member = DB::table('master_group_participants')
                ->where('master_group_session_id', $row->id)
                ->where('id', $participant)->lockForUpdate()->firstOrFail();

            if ($member->status === 'cancelled') {
                return; // Repeated requests must not free a second seat.
            }
            if ($member->status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'booking' => 'Этот статус участника нельзя отменить.',
                ]);
            }
            if ($row->reserved_seats < 1) {
                throw ValidationException::withMessages([
                    'booking' => 'Обнаружено расхождение мест. Проверьте занятие.',
                ]);
            }
            DB::table('master_group_participants')
                ->where('id', $member->id)->update([
                    'status' => 'cancelled', 'updated_at' => now(),
                ]);
            DB::table('master_group_sessions')
                ->where('id', $row->id)->update([
                    'reserved_seats' => $row->reserved_seats - 1,
                    'updated_at' => now(),
                ]);
        }, 3);

        return back()->with('success', 'Участник снят с занятия. Место освобождено.');
    }

}
