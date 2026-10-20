<?php

namespace App\Http\Controllers;

use App\Models\MasterProfile;
use App\Services\BookAppointment;
use App\Services\WhatsApp;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualBookingController extends Controller
{
    private function master(Request $request): MasterProfile
    {
        return MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function clients(Request $request)
    {
        $master = $this->master($request);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = DB::table('master_clients')->where('master_profile_id', $master->id);
        $search = trim($data['q'] ?? '');
        if ($search !== '') {
            // Escape LIKE metacharacters so a phone/name search stays literal.
            $name = '%'.addcslashes($search, '%_\\').'%';
            $digits = preg_replace('/\D/', '', $search);
            $query->where(function ($query) use ($name, $digits) {
                $query->where('name', 'like', $name);
                if ($digits !== '') {
                    $query->orWhere('phone_digits', 'like', '%'.$digits.'%');
                }
            });
        }
        return response()->json($query->orderByDesc('updated_at')->limit(20)->get(['id', 'name', 'phone_digits']))
            ->header('Cache-Control', 'private, no-store');
    }

    public function slots(Request $request, WorkingHours $hours)
    {
        $master = $this->master($request);
        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$today->toDateString(),
                'before_or_equal:'.$today->addDays(90)->toDateString()],
            'service_id' => ['required', 'integer'],
        ]);
        $service = $master->services()->where('is_active', true)->findOrFail($data['service_id']);
        $day = CarbonImmutable::parse($data['date'], $master->timezone)->startOfDay();
        $startUtc = $day->utc();
        $endUtc = $day->addDay()->utc();
        $busy = $master->appointments()->whereIn('status', ['pending', 'confirmed'])
            ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
        $blocks = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
            ->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
        $groups = DB::table('master_group_sessions')->where('master_profile_id', $master->id)
            ->where('status', 'open')->where('starts_at', '<', $endUtc)->where('ends_at', '>', $startUtc)->get();
        $slots = [];
        for ($cursor = $day; $cursor->lt($day->addDay()); $cursor = $cursor->addMinutes(15)) {
            $start = $cursor->utc();
            $end = $start->addMinutes($service->duration_minutes);
            if ($start->lte(CarbonImmutable::now('UTC')) || ! $hours->contains($master, $start, $end)) {
                continue;
            }
            $overlap = $busy->contains(fn ($a) => $a->starts_at->lt($end) && $a->ends_at->gt($start));
            $closed = $blocks->concat($groups)->contains(fn ($b) =>
                CarbonImmutable::parse($b->starts_at, 'UTC')->lt($end) &&
                CarbonImmutable::parse($b->ends_at, 'UTC')->gt($start));
            if (! $overlap && ! $closed) {
                $slots[] = ['value' => $start->toIso8601String(), 'label' => $cursor->format('H:i')];
            }
        }
        return response()->json($slots)->header('Cache-Control', 'private, no-store');
    }

    public function create(Request $request, BookAppointment $booking)
    {
        $master = $this->master($request);
        $data = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'client_name' => ['required_without:client_id', 'nullable', 'string', 'max:100'],
            'client_phone' => ['required_without:client_id', 'nullable', 'string', 'regex:/^[0-9 ()-]{7,30}$/',
                function ($attribute, $value, $fail) {
                    if ($value && ! WhatsApp::phone($value)) {
                        $fail('Введите телефон с кодом страны, без +.');
                    }
                }],
            'service_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i:sP', 'after:now',
                'before:'.now('UTC')->addDays(91)->toIso8601String()],
            'client_comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $client = empty($data['client_id']) ? null : DB::table('master_clients')
            ->where('master_profile_id', $master->id)->where('id', $data['client_id'])->first();
        abort_if(! empty($data['client_id']) && ! $client, 404);
        $name = trim($client?->name ?? $data['client_name'] ?? '');
        $phone = $client?->phone_digits ?? $data['client_phone'] ?? '';
        if ($name === '' || ! WhatsApp::phone($phone)) {
            throw ValidationException::withMessages(['client_phone' => 'Укажите имя и корректный телефон клиента с кодом страны.']);
        }
        $service = $master->services()->where('is_active', true)->findOrFail($data['service_id']);
        $appointment = $booking->create($master, $service, CarbonImmutable::parse($data['starts_at'])->utc(),
            $name, $phone, $data['client_comment'] ?? null, false, true);
        return redirect()->route('dashboard', [
            'calendar_date' => $appointment->starts_at->setTimezone($master->timezone)->format('Y-m-d'),
        ])->with('success', 'Клиент записан. Запись добавлена в календарь.');
    }
}
