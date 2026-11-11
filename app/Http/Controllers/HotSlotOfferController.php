<?php

namespace App\Http\Controllers;

use App\Models\HotSlotOffer;
use App\Models\MasterProfile;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HotSlotOfferController extends Controller
{
    private function master(Request $request): MasterProfile
    {
        return MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function store(Request $request, WorkingHours $hours)
    {
        $master = $this->master($request);
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'special_price_som' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'story_template' => ['sometimes', Rule::in(['white', 'black', 'red'])],
            'slots' => ['required', 'array', 'min:1', 'max:10'],
            'slots.*' => ['required', 'date_format:Y-m-d\TH:i', 'distinct'],
        ]);
        $service = $master->services()->where('is_active', true)->find($data['service_id']);
        if (! $service || ! $master->booking_enabled || $master->is_demo) {
            throw ValidationException::withMessages(['service_id' => 'Услуга или онлайн-запись недоступна.']);
        }
        $starts = collect($data['slots'])->map(fn ($value) => CarbonImmutable::parse($value, $master->timezone)->utc())->sort()->values();
        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        foreach ($starts as $index => $start) {
            $local = $start->setTimezone($master->timezone);
            $end = $start->addMinutes($service->duration_minutes);
            if ($start->lessThanOrEqualTo(CarbonImmutable::now('UTC')) || $local->minute % 30 !== 0 ||
                $local->second !== 0 || $local->startOfDay()->lessThan($today) ||
                $local->startOfDay()->greaterThan($today->addDays(90)) || ! $hours->contains($master, $start, $end)) {
                throw ValidationException::withMessages(["slots.$index" => 'Выберите будущее время в рабочем расписании.']);
            }
            $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            $blocked = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            $group = DB::table('master_group_sessions')->where('master_profile_id', $master->id)->where('status', 'open')
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
            if ($busy || $blocked || $group) {
                throw ValidationException::withMessages(["slots.$index" => 'Это время уже занято.']);
            }
        }

        $offer = DB::transaction(function () use ($master, $service, $data, $starts) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            // Booking uses the same profile lock. Rechecking after acquiring it
            // closes the gap between validation and offer creation.
            foreach ($starts as $index => $start) {
                $end = $start->addMinutes($service->duration_minutes);
                $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $blocked = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $group = DB::table('master_group_sessions')->where('master_profile_id', $master->id)->where('status', 'open')
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                if ($busy || $blocked || $group) {
                    throw ValidationException::withMessages(["slots.$index" => 'Это время уже занято.']);
                }
            }
            $offer = HotSlotOffer::create([
                'master_profile_id' => $master->id,
                'master_service_id' => $service->id,
                'token' => Str::random(40),
                'special_price_som' => $data['special_price_som'] ?? null,
                'story_template' => $data['story_template'] ?? 'white',
            ]);
            foreach ($starts as $start) {
                $offer->slots()->create([
                    'starts_at' => $start->toDateTimeString(),
                    'ends_at' => $start->addMinutes($service->duration_minutes)->toDateTimeString(),
                ]);
            }
            return $offer;
        }, 3);

        return response()->json(['id' => $offer->id, 'url' => route('hot-slots.visit', $offer->token)]);
    }

    public function cancel(Request $request, HotSlotOffer $offer)
    {
        $master = $this->master($request);
        abort_unless($offer->master_profile_id === $master->id, 404);
        DB::transaction(function () use ($master, $offer) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            HotSlotOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail()->update(['cancelled_at' => now('UTC')]);
        }, 3);
        return back()->with('success', 'Предложение отменено.');
    }

    public function visit(HotSlotOffer $offer)
    {
        $offer->increment('clicks');
        return redirect()->route('public.master', ['master' => $offer->masterProfile->slug, 'offer' => $offer->token]);
    }
}
