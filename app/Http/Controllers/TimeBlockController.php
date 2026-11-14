<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TimeBlockController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'starts_at_local' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at_local' => ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at_local'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        DB::transaction(function () use ($master, $data) {
            // The same owner lock serializes all booking and calendar mutations.
            $locked = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_demo, 403);
            $startLocal = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at_local'], $locked->timezone);
            $endLocal = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['ends_at_local'], $locked->timezone);
            $today = CarbonImmutable::now($locked->timezone)->startOfDay();
            if (! $startLocal || ! $endLocal || $startLocal->format('Y-m-d\\TH:i') !== $data['starts_at_local']
                || $endLocal->format('Y-m-d\\TH:i') !== $data['ends_at_local']
                || $startLocal->lt($today) || $startLocal->gt($today->addYear())
                || $endLocal->lte($startLocal) || $endLocal->gt($startLocal->addDays(31))) {
                throw ValidationException::withMessages(['ends_at_local' => 'Выберите интервал до 31 дня в течение ближайшего года.']);
            }
            $start = $startLocal->utc();
            $end = $endLocal->utc();
            if ($locked->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()
                || DB::table('master_group_sessions')->where('master_profile_id', $locked->id)->where('status', 'open')
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at_local' => 'В этом интервале уже есть запись или групповое занятие. Выберите другое время.']);
            }
            if (DB::table('master_time_blocks')->where('master_profile_id', $locked->id)
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at_local' => 'Часть этого интервала уже закрыта. Выберите другое время.']);
            }
            DB::table('master_time_blocks')->insert([
                'master_profile_id' => $locked->id,
                'starts_at' => $start->format('Y-m-d H:i:s'), 'ends_at' => $end->format('Y-m-d H:i:s'),
                'reason' => trim($data['reason'] ?? '') ?: null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
        return back()->with('success', 'Время закрыто для записи.');
    }

    public function destroy(Request $request, int $block)
    {
        $master = MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
        DB::transaction(function () use ($master, $block) {
            $locked = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_demo, 403);
            $row = DB::table('master_time_blocks')->where('master_profile_id', $locked->id)->where('id', $block)->first();
            abort_unless($row, 404);
            DB::table('master_time_blocks')->where('id', $row->id)->where('master_profile_id', $locked->id)->delete();
        }, 3);
        return back()->with('success', 'Время снова открыто для записи.');
    }
}
