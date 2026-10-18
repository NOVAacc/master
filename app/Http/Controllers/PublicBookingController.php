<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\BookAppointment;
use App\Services\WhatsApp;
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
            'services' => ['sometimes', 'array', 'max:20'],
            'selection' => ['sometimes', 'boolean'],
            'services.*' => ['required', 'integer', 'distinct'],
        ]);
        $date = $data['date'] ?? $today->toDateString();
        $services = $master->services()->where('is_active', true)->orderBy('sort_order')->get();
        $ids = isset($data['selection']) ? ($data['services'] ?? []) : ($data['services'] ?? (isset($data['service']) ? [(int) $data['service']] : [$services->first()?->id]));
        $selected = $services->whereIn('id', $ids)->values();
        abort_if(count(array_filter($ids)) !== $selected->count(), 404);
        $duration = $selected->sum('duration_minutes');
        $slots = [];
        if ($master->booking_enabled && $selected->isNotEmpty()) {
            $day = CarbonImmutable::parse($date, $master->timezone);
            $startUtc = $day->utc();
            $endUtc = $day->addDay()->utc();
            $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
            $blocks = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
            for ($cursor = $day; $cursor->lessThan($day->addDay()); $cursor = $cursor->addMinutes(30)) {
                $start = $cursor->utc();
                $end = $start->addMinutes($duration);
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
            'master' => $master->only('slug', 'display_name', 'specialty', 'bio', 'address', 'timezone', 'booking_enabled', 'auto_confirm', 'instagram_username', 'instagram_url', 'avatar_url', 'cover_url', 'profile_theme'),
            'services' => $services->map->only(['id', 'name', 'description', 'duration_minutes', 'price_som']),
            'selectedService' => $selected->first()?->id,
            'selectedServices' => $selected->pluck('id')->all(),
            'workingDays' => DB::table('master_working_hours')->where('master_profile_id', $master->id)->pluck('weekday')->map(fn ($day) => (int) $day)->all(),
            'date' => $date, 'minDate' => $today->toDateString(),
            'maxDate' => $today->addDays(90)->toDateString(), 'slots' => $slots,
            'whatsappAutomatic' => app(WhatsApp::class)->configured(),
            'bookingReceipt' => $request->session()->get('booking_receipt.master_slug') === $master->slug ? $request->session()->get('booking_receipt') : null,
        ]);
    }

    public function book(Request $request, MasterProfile $master, BookAppointment $booking)
    {
        $data = $request->validate([
            'service_id' => ['required_without:service_ids', 'integer'],
            'service_ids' => ['required_without:service_id', 'array', 'min:1', 'max:20'],
            'service_ids.*' => ['required', 'integer', 'distinct'],
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
            'whatsapp_consent' => ['sometimes', 'boolean'],
        ]);
        $ids = $data['service_ids'] ?? [$data['service_id']];
        $services = $master->services()->where('is_active', true)->whereIn('id', $ids)->get();
        abort_if($services->count() !== count($ids), 404);
        $appointment = $booking->create($master, $services->all(), CarbonImmutable::parse($data['starts_at'])->utc(),
            $data['client_name'], $data['client_phone'], $data['client_comment'] ?? null, (bool) ($data['whatsapp_consent'] ?? false));

        return redirect()->route('public.master', $master->slug)->with('booking_receipt', [
            'master_slug' => $master->slug,
            'status' => $appointment->status,
            'text' => app(WhatsApp::class)->message($appointment, 'client'),
            'masterLink' => app(WhatsApp::class)->link($master->phone, app(WhatsApp::class)->message($appointment, 'master')),
            'automaticStatus' => $appointment->notifications()->where('recipient', 'client')->value('status'),
        ])->with('success',
            $appointment->status === 'confirmed' ? 'Запись подтверждена. Ждём вас!' : 'Заявка отправлена. Мастер свяжется с вами для подтверждения.');
    }
}
