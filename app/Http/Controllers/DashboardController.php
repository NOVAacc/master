<?php

namespace App\Http\Controllers;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Rules\ProfileImage;
use App\Services\EditAppointment;
use App\Services\WhatsApp;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private function master(Request $request): MasterProfile
    {
        return MasterProfile::where('user_id', $request->user()->id)->firstOrFail();
    }

    public function show(Request $request)
    {
        $master = $this->master($request);

        $today = CarbonImmutable::now($master->timezone)->startOfDay();
        $data = $request->validate(['calendar_date' => ['nullable', 'date_format:Y-m-d',
            'after_or_equal:'.$today->subYears(5)->toDateString(), 'before_or_equal:'.$today->addYears(5)->toDateString()]]);
        $calendarDate = $data['calendar_date'] ?? $today->toDateString();
        $calendarStart = CarbonImmutable::parse($calendarDate, $master->timezone)->startOfWeek();
        $calendarEnd = $calendarStart->addWeek();
        $presentAppointment = function ($appointment) use ($master) {
            $appointment->setRelation('masterProfile', $master);
            $appointment->setAttribute('whatsapp_link', app(WhatsApp::class)->link($appointment->client_phone, app(WhatsApp::class)->message($appointment, 'client')));
            $appointment->unsetRelation('masterProfile');
            $appointment->setRelation('notifications', $appointment->notifications->filter(fn ($n) => (int) $n->revision === (int) $appointment->notification_revision)->values());

            return $appointment;
        };

        // Fetch group attendees once, scoped to the authenticated master.
        // No private participant details are exposed by public booking endpoints.
        $groupParticipants = DB::table('master_group_participants as p')
            ->join('master_group_sessions as s', 's.id', '=', 'p.master_group_session_id')
            ->where('s.master_profile_id', $master->id)
            ->where('s.ends_at', '>', now('UTC')->subDays(7))
            ->orderBy('p.id')
            ->get(['p.id', 'p.master_group_session_id', 'p.client_name', 'p.client_phone', 'p.status'])
            ->groupBy('master_group_session_id');

        return Inertia::render('Dashboard', [
            'industries' => config('industries'),
            'calendarDate' => $calendarDate,
            'calendarAppointments' => $master->appointments()->where('starts_at', '<', $calendarEnd->utc())
                ->where('ends_at', '>', $calendarStart->utc())
                ->with(['notifications:id,master_appointment_id,recipient,status,error_code,revision', 'changes'])->orderBy('starts_at')->get()->map($presentAppointment),
            'calendarBlocks' => DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                ->where('starts_at', '<', $calendarEnd->utc())->where('ends_at', '>', $calendarStart->utc())->get()
                ->map(fn ($block) => ['id' => $block->id, 'starts_at' => CarbonImmutable::parse($block->starts_at, 'UTC')->toIso8601String(),
                    'ends_at' => CarbonImmutable::parse($block->ends_at, 'UTC')->toIso8601String(), 'reason' => $block->reason]),
            'master' => $master,
            'security' => [
                'email' => $request->user()->email,
                'emailVerified' => $request->user()->email_verified_at !== null,
                'passwordSet' => $request->user()->password_initialized_at !== null,
            ],
            'groupSessions' => DB::table('master_group_sessions')
                ->where('master_profile_id', $master->id)
                ->where('ends_at', '>', now('UTC')->subDays(7))
                ->orderBy('starts_at')->limit(100)
                ->get(['id', 'master_service_id', 'service_name_snapshot', 'starts_at', 'ends_at', 'capacity', 'reserved_seats', 'status', 'price_som'])
                ->map(fn ($session) => [
                    'participants' => ($groupParticipants->get($session->id) ?? collect())->values(),
                    'id' => $session->id,
                    'service_name_snapshot' => $session->service_name_snapshot,
                    'starts_at' => CarbonImmutable::parse($session->starts_at, 'UTC')->toIso8601String(),
                    'ends_at' => CarbonImmutable::parse($session->ends_at, 'UTC')->toIso8601String(),
                    'capacity' => $session->capacity,
                    'reserved_seats' => $session->reserved_seats,
                    'status' => $session->status,
                    'price_som' => $session->price_som,
                ]),
            'clients' => DB::table('master_clients')
                ->where('master_profile_id', $master->id)
                ->whereNull('archived_at')
                ->orderByDesc('updated_at')
                ->limit(200)
                ->get(['id', 'name', 'phone_digits', 'notes']),
            'services' => $master->services()->orderBy('sort_order')->get(),
            'hours' => DB::table('master_working_hours')->where('master_profile_id', $master->id)->orderBy('weekday')->get()
                ->map(fn ($hour) => [
                    'weekday' => (int) $hour->weekday,
                    'starts_at' => substr($hour->starts_at, 0, 5),
                    'ends_at' => substr($hour->ends_at, 0, 5),
                    'break_starts_at' => $hour->break_starts_at ? substr($hour->break_starts_at, 0, 5) : null,
                    'break_ends_at' => $hour->break_ends_at ? substr($hour->break_ends_at, 0, 5) : null,
                ]),
            'appointments' => $master->appointments()->where('ends_at', '>=', now('UTC')->subDays(30))
                ->with(['notifications:id,master_appointment_id,recipient,status,error_code,revision', 'changes'])->orderBy('starts_at')->limit(200)->get()->map($presentAppointment),
            'whatsappAutomatic' => app(WhatsApp::class)->configured(),
            'publicUrl' => route('public.master', $master->slug),
        ]);
    }

    public function profile(Request $request)
    {
        if ($request->has('instagram_username')) {
            $value = trim((string) $request->input('instagram_username'));
            if (str_contains(strtolower($value), 'instagram.com/')) {
                $url = parse_url(preg_match('~^https?://~i', $value) ? $value : 'https://'.$value);
                if (in_array(strtolower($url['host'] ?? ''), ['instagram.com', 'www.instagram.com'], true)) {
                    $value = trim($url['path'] ?? '', '/');
                }
            }
            $request->merge(['instagram_username' => ltrim($value, '@') ?: null]);
        }
        $master = $this->master($request);
        $request->merge(['slug' => strtolower(trim((string) $request->input('slug', $master->slug)))]);
        $slugRules = $request->input('slug') === $master->slug
            ? ['required', 'string'] // Keep legacy links unchanged while editing other fields.
            : ['required', 'string', 'regex:/^[a-z0-9]{3,30}$/', Rule::unique('master_profiles', 'slug')->ignore($master->id)];
        $industries = config('industries');
        $industry = $request->input('industry', $master->industry);
        $activities = is_string($industry) ? ($industries[$industry]['activities'] ?? []) : [];
        $data = $request->validate([
            'industry' => ['sometimes', 'nullable', 'string', Rule::in(array_keys($industries))],
            'activity' => ['sometimes', 'nullable', 'string', Rule::in(array_keys($activities))],
            'slug' => $slugRules,
            'display_name' => ['required', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'instagram_username' => ['nullable', 'string', 'regex:/^(?!\.)(?!.*\.\.)(?!.*\.$)[A-Za-z0-9_.]{1,30}$/'],
            'profile_theme' => ['sometimes', 'in:graphite,sand,sage,lilac'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'work1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_work1' => ['sometimes', 'boolean'],
            'work2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_work2' => ['sometimes', 'boolean'],
            'work3' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', new ProfileImage, 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'remove_work3' => ['sometimes', 'boolean'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'remove_cover' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_url' => ['nullable', 'string', 'max:2048', 'url:https',
                function ($attribute, $value, $fail) {
                    $url = parse_url($value);
                    $host = strtolower($url['host'] ?? '');
                    $allowed = false;
                    foreach (['2gis.kg', '2gis.ru', '2gis.com', '2gis.kz', '2gis.uz', '2gis.ae'] as $domain) {
                        if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                            $allowed = true;
                            break;
                        }
                    }
                    if (! $allowed || isset($url['user']) || isset($url['pass']) || isset($url['port'])) {
                        $fail('Вставьте ссылку HTTPS из 2ГИС.');
                    }
                }],
            'timezone' => ['required', 'timezone'],
            'booking_enabled' => ['required', 'boolean'],
            'auto_confirm' => ['required', 'boolean'],
            'whatsapp_notifications' => ['sometimes', 'boolean'],
        ]);
        if (array_key_exists('industry', $data) && $data['industry'] !== $master->industry && ! array_key_exists('activity', $data)) {
            $data['activity'] = null;
        }
        if (($data['whatsapp_notifications'] ?? false) && ! WhatsApp::phone($data['phone'] ?? null)) {
            throw ValidationException::withMessages(['phone' => 'Укажите номер WhatsApp с кодом страны.']);
        }
        $disk = Storage::disk('local');
        $uploaded = [];
        $oldPaths = [];
        try {
            foreach (['avatar', 'cover', 'work1', 'work2', 'work3'] as $kind) {
                if ($request->hasFile($kind)) {
                    $path = $request->file($kind)->store('profile-images/'.$master->id, 'local');
                    if (! $path) {
                        throw ValidationException::withMessages([$kind => 'Не удалось сохранить изображение. Попробуйте ещё раз.']);
                    }
                    $uploaded[$kind] = $path;
                }
            }
            DB::transaction(function () use ($master, $data, $uploaded, &$oldPaths) {
                $locked = MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
                $values = Arr::except($data, ['avatar', 'cover', 'work1', 'work2', 'work3', 'remove_avatar', 'remove_cover', 'remove_work1', 'remove_work2', 'remove_work3']);
                foreach (['avatar', 'cover', 'work1', 'work2', 'work3'] as $kind) {
                    if (isset($uploaded[$kind]) || ($data['remove_'.$kind] ?? false)) {
                        if ($locked->{$kind.'_path'} && preg_match('~^profile-images/'.$locked->id.'/[A-Za-z0-9]{40}\.(?:jpg|jpeg|png|webp)$~D', $locked->{$kind.'_path'})) {
                            $oldPaths[] = $locked->{$kind.'_path'};
                        }
                        $values[$kind.'_path'] = $uploaded[$kind] ?? null;
                    }
                }
                $locked->update($values);
            });
        } catch (\Throwable $error) {
            foreach ($uploaded as $path) {
                $disk->delete($path);
            }
            throw $error;
        }
        foreach ($oldPaths as $path) {
            $disk->delete($path);
        }

        return back()->with('success', 'Профиль сохранён.');
    }

    private function serviceData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'],
            'price_som' => ['required', 'integer', 'min:0', 'max:10000000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    public function createService(Request $request)
    {
        $this->master($request)->services()->create($this->serviceData($request));

        return back()->with('success', 'Услуга добавлена.');
    }

    public function updateService(Request $request, MasterService $service)
    {
        $master = $this->master($request);
        abort_unless($service->master_profile_id === $master->id, 404);
        $data = $this->serviceData($request);
        DB::transaction(function () use ($master, $service, $data) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            $service->update($data);
        });

        return back()->with('success', 'Услуга сохранена.');
    }

    public function schedule(Request $request)
    {
        $data = $request->validate([
            'hours' => ['present', 'array', 'max:7'],
            'hours.*.weekday' => ['required', 'integer', 'between:1,7', 'distinct'],
            'hours.*.starts_at' => ['required', 'date_format:H:i'],
            'hours.*.ends_at' => ['required', 'date_format:H:i', 'after:hours.*.starts_at'],
            'hours.*.break_starts_at' => ['nullable', 'date_format:H:i'],
            'hours.*.break_ends_at' => ['nullable', 'date_format:H:i'],
        ]);
        foreach ($data['hours'] as $index => $hour) {
            $start = $hour['starts_at'];
            $end = $hour['ends_at'];
            $breakStart = $hour['break_starts_at'] ?? null;
            $breakEnd = $hour['break_ends_at'] ?? null;
            if ($start >= $end) {
                throw ValidationException::withMessages([
                    "hours.$index.ends_at" => 'Конец рабочего дня должен быть позже начала.',
                ]);
            }
            if (($breakStart === null) !== ($breakEnd === null) ||
                ($breakStart !== null && ($breakStart < $start || $breakEnd > $end || $breakStart >= $breakEnd))) {
                throw ValidationException::withMessages([
                    "hours.$index.break_starts_at" => 'Обед должен быть внутри рабочего дня; укажите начало и конец перерыва.',
                ]);
            }
        }
        $master = $this->master($request);
        DB::transaction(function () use ($master, $data) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            DB::table('master_working_hours')->where('master_profile_id', $master->id)->delete();
            foreach ($data['hours'] as $hour) {
                DB::table('master_working_hours')->insert([
                    'weekday' => (int) $hour['weekday'],
                    'starts_at' => $hour['starts_at'],
                    'ends_at' => $hour['ends_at'],
                    'break_starts_at' => $hour['break_starts_at'] ?? null,
                    'break_ends_at' => $hour['break_ends_at'] ?? null,
                    'master_profile_id' => $master->id,
                    'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Расписание сохранено.');
    }

    public function editAppointment(Request $request, MasterAppointment $appointment, EditAppointment $editor)
    {
        $master = $this->master($request);
        abort_unless($appointment->master_profile_id === $master->id, 404);
        $data = $request->validate([
            'revision' => ['required', 'integer', 'min:1'],
            'starts_at_local' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration_minutes' => ['required', 'integer', 'between:5,1440'],
            'price_som' => ['required', 'integer', 'between:0,200000000'],
            'service_ids' => ['required', 'array', 'min:1', 'max:20'],
            'service_ids.*' => ['required', 'integer', 'distinct'],
            'client_name' => ['required', 'string', 'max:100'],
            'client_phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,30}$/', function ($attribute, $value, $fail) {
                if (! WhatsApp::phone($value)) {
                    $fail('Введите телефон с кодом страны.');
                }
            }],
            'client_comment' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $editor->update($master, $appointment, $data, $request->user()->id);

        return redirect()->route('dashboard', ['calendar_date' => substr($data['starts_at_local'], 0, 10)])->with('success', 'Изменения сохранены. Проверьте, что клиент знает новое время и стоимость.');
    }

    public function retryWhatsApp(Request $request, MasterAppointment $appointment)
    {
        abort_unless($appointment->master_profile_id === $this->master($request)->id, 404);
        abort_unless($appointment->status === 'confirmed', 422);
        foreach ($appointment->notifications as $notification) {
            app(WhatsApp::class)->send($notification);
        }

        return back()->with('success', 'Статусы WhatsApp обновлены. Проверьте результат в записи.');
    }

    public function status(Request $request, MasterAppointment $appointment)
    {
        $master = $this->master($request);
        abort_unless($appointment->master_profile_id === $master->id, 404);
        $data = $request->validate(['status' => ['required', 'in:confirmed,cancelled,completed,no_show']]);
        DB::transaction(function () use ($master, $appointment, $data) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            $appointment->refresh();
            // Terminal appointments cannot be reopened into an already reused slot.
            abort_unless(in_array($appointment->status, ['pending', 'confirmed']), 422);
            if (in_array($data['status'], ['completed', 'no_show'])) {
                abort_if($appointment->ends_at->isFuture(), 422);
            }
            if ($appointment->status !== $data['status']) {
                $appointment->revision++;
            }
            $attendance = match ($data['status']) {
                'completed' => 'attended',
                'no_show' => 'missed',
                'cancelled' => 'not_applicable',
                default => 'expected',
            };
            $appointment->fill($data + ['attendance_status' => $attendance])->save();
        });

        app(WhatsApp::class)->confirmed($appointment->fresh());

        return back()->with('success', 'Статус записи изменён.');
    }
}
