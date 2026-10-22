<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SlotStoryController extends Controller
{
    private const STYLES = ['minimal', 'graphite', 'soft', 'accent', 'premium'];

    public function availability(Request $request, WorkingHours $hours): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
        ]);
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        $service = $master->services()->whereKey($data['service_id'])->where('is_active', true)->firstOrFail();
        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], $master->timezone);
        if (! $date || $date->lt($today) || $date->gt($today->addDays(90))) {
            return response()->json(['available' => false, 'reason' => 'Дата вне доступного периода.']);
        }
        $startLocal = CarbonImmutable::parse($data['date'].' '.$data['time'].':00', $master->timezone);
        if ($startLocal->format('Y-m-d H:i') !== $data['date'].' '.$data['time']
            || (int) $startLocal->minute % 30 !== 0) {
            return response()->json(['available' => false, 'reason' => 'Выберите время с шагом 30 минут.']);
        }
        $start = $startLocal->utc();
        $end = $start->addMinutes($service->duration_minutes);
        $available = $master->booking_enabled && $start->gt(CarbonImmutable::now('UTC'))
            && $hours->contains($master, $start, $end)
            && ! $master->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()
            && ! DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()
            && ! DB::table('master_group_sessions')->where('master_profile_id', $master->id)
                ->where('status', 'open')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();

        return response()->json([
            'available' => $available,
            'reason' => $available ? null : 'Это время уже занято или недоступно для записи.',
            'url' => $available ? route('public.master', $master->slug).'?'.http_build_query([
                'date' => $data['date'], 'service' => $service->id, 'time' => $data['time'],
            ]) : null,
        ]);
    }

    public function preference(Request $request): JsonResponse
    {
        $data = $request->validate(['style' => ['required', Rule::in(self::STYLES)]]);
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        $master->story_style = $data['style'];
        $master->save();

        return response()->json(['style' => $master->story_style]);
    }
}
