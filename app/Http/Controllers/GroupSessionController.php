<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\WorkingHours;
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

    public function register(Request $request, int $session)
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'regex:/^\\+?[0-9 ()-]{7,30}$/'],
            'consent' => ['accepted'],
        ]);
        DB::transaction(function () use ($session, $data) {
            // Lock the master first, like all other booking operations.
            $ownerId = DB::table('master_group_sessions')->where('id', $session)->value('master_profile_id');
            abort_unless($ownerId, 404);
            $master = MasterProfile::whereKey($ownerId)->lockForUpdate()->firstOrFail();
            $row = DB::table('master_group_sessions')->where('id', $session)
                ->where('master_profile_id', $master->id)->lockForUpdate()->firstOrFail();
            if ($row->status !== 'open' || $row->reserved_seats >= $row->capacity
                || CarbonImmutable::parse($row->starts_at, 'UTC')->isPast()) {
                throw ValidationException::withMessages(['booking' => 'Нет свободных мест.']);
            }
            DB::table('master_group_participants')->insert([
                'master_group_session_id' => $session,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'],
                'status' => 'confirmed',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('master_group_sessions')->where('id', $session)
                ->update(['reserved_seats' => $row->reserved_seats + 1, 'updated_at' => now()]);
        }, 3);

        return back()->with('success', 'Место забронировано.');
    }
}
