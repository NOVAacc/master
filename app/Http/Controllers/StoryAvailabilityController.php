<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoryAvailabilityController extends Controller
{
    public function __invoke(Request $request, WorkingHours $hours)
    {
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'service' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);
        $day = CarbonImmutable::parse($data['date'], $master->timezone)->startOfDay();
        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        if ($day->lessThan($today) || $day->greaterThan($today->addDays(90))) {
            return response()->json(['available' => false, 'reason' => 'Дата вне периода записи.']);
        }
        $service = $master->services()->where('is_active', true)->find($data['service']);
        if (! $service || ! $master->booking_enabled || $master->is_demo) {
            return response()->json(['available' => false, 'reason' => 'Услуга или запись недоступна.']);
        }
        $start = CarbonImmutable::parse($data['date'].' '.$data['time'], $master->timezone)->utc();
        $end = $start->addMinutes($service->duration_minutes);
        if ($start->lessThanOrEqualTo(CarbonImmutable::now('UTC')) ||
            ((int) $day->diffInMinutes($start->setTimezone($master->timezone), false)) % 30 !== 0 ||
            ! $hours->contains($master, $start, $end)) {
            return response()->json(['available' => false, 'reason' => 'Время вне расписания.']);
        }
        $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
        $block = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
        $group = DB::table('master_group_sessions')->where('master_profile_id', $master->id)
            ->where('status', 'open')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
        return response()->json([
            'available' => ! ($busy || $block || $group),
            'reason' => $busy || $block || $group ? 'Время уже занято.' : null,
        ]);
    }
}
