<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\BookAppointment;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PublicBookingController extends Controller
{
    public function show(Request $request, MasterProfile $master, WorkingHours $hours)
    {
        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$today->toDateString(),
                'before_or_equal:'.$today->addDays(90)->toDateString()],
            'service' => ['nullable', 'integer'],
        ]);
        $date = $data['date'] ?? $today->toDateString();
        $services = $master->services()->where('is_active', true)->orderBy('sort_order')->get();
        $selected = isset($data['service']) ? $services->firstWhere('id', (int) $data['service']) : $services->first();
        $slots = [];
        if ($master->booking_enabled && $selected) {
            $day = CarbonImmutable::parse($date, $master->timezone);
            $startUtc = $day->utc();
            $endUtc = $day->addDay()->utc();
            $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
            $blocks = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
            for ($cursor = $day; $cursor->lessThan($day->addDay()); $cursor = $cursor->addMinutes(30)) {
                $start = $cursor->utc();
                $end = $start->addMinutes($selected->duration_minutes);
                if ($start->lessThanOrEqualTo(CarbonImmutable::now('UTC')) || ! $hours->contains($master, $start, $end)) {
                    continue;
                }
                $overlap = $busy->contains(fn ($a) => $a->starts_at->lt($end) && $a->ends_at->gt($start)) ||
                    $blocks->contains(fn ($b) => CarbonImmutable::parse($b->starts_at, 'UTC')->lt($end) &&
                        CarbonImmutable::parse($b->ends_at, 'UTC')->gt($start));
                if (! $overlap) {
                    $slots[] = ['value' => $start->toIso8601String(), 'label' => $cursor->format('H:i')];
                }
            }
        }

        return Inertia::render('Booking', [
            'master' => $master->only('slug', 'display_name', 'specialty', 'bio', 'address', 'timezone', 'booking_enabled'),
            'services' => $services->map->only(['id', 'name', 'description', 'duration_minutes', 'price_som']),
            'selectedService' => $selected?->id, 'date' => $date, 'minDate' => $today->toDateString(),
            'maxDate' => $today->addDays(90)->toDateString(), 'slots' => $slots,
        ]);
    }

    public function book(Request $request, MasterProfile $master, BookAppointment $booking)
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i:sP', 'after:now', 'before:'.now('UTC')->addDays(91)->toIso8601String()],
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,30}$/',
                function ($attribute, $value, $fail) {
                    $length = strlen(preg_replace('/\D/', '', $value));
                    if ($length < 7 || $length > 15) {
                        $fail('Введите корректный номер телефона.');
                    }
                }],
            'client_comment' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ]);
        $service = $master->services()->where('is_active', true)->findOrFail($data['service_id']);
        $appointment = $booking->create($master, $service, CarbonImmutable::parse($data['starts_at'])->utc(),
            $data['client_name'], $data['client_phone'], $data['client_comment'] ?? null);

        return redirect()->route('public.master', $master->slug)->with('success',
            $appointment->status === 'confirmed' ? 'Запись подтверждена. Ждём вас!' : 'Заявка отправлена. Мастер свяжется с вами для подтверждения.');
    }
}
