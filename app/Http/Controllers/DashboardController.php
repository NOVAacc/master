<?php

namespace App\Http\Controllers;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\MasterService;
use App\Services\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        return Inertia::render('Dashboard', [
            'master' => $master,
            'services' => $master->services()->orderBy('sort_order')->get(),
            'hours' => DB::table('master_working_hours')->where('master_profile_id', $master->id)->orderBy('weekday')->get()
                ->map(fn ($hour) => [
                    'weekday' => (int) $hour->weekday,
                    'starts_at' => substr($hour->starts_at, 0, 5),
                    'ends_at' => substr($hour->ends_at, 0, 5),
                ]),
            'appointments' => $master->appointments()->where('ends_at', '>=', now('UTC')->subDays(30))
                ->with('notifications:id,master_appointment_id,recipient,status,error_code')->orderBy('starts_at')->limit(200)->get()->map(function ($appointment) use ($master) {
                    $appointment->setRelation('masterProfile', $master);
                    $appointment->setAttribute('whatsapp_link', app(WhatsApp::class)->link($appointment->client_phone, app(WhatsApp::class)->message($appointment, 'client')));

                    $appointment->unsetRelation('masterProfile');

                    return $appointment;
                }),
            'whatsappAutomatic' => app(WhatsApp::class)->configured(),
            'publicUrl' => route('public.master', $master->slug),
        ]);
    }

    public function profile(Request $request)
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'specialty' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'timezone'],
            'booking_enabled' => ['required', 'boolean'],
            'auto_confirm' => ['required', 'boolean'],
            'whatsapp_notifications' => ['sometimes', 'boolean'],
        ]);
        if (($data['whatsapp_notifications'] ?? false) && ! WhatsApp::phone($data['phone'] ?? null)) {
            throw ValidationException::withMessages(['phone' => 'Укажите номер WhatsApp с кодом страны.']);
        }
        $master = $this->master($request);
        DB::transaction(function () use ($master, $data) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail()->update($data);
        });

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
        ]);
        $master = $this->master($request);
        DB::transaction(function () use ($master, $data) {
            MasterProfile::whereKey($master->id)->lockForUpdate()->firstOrFail();
            DB::table('master_working_hours')->where('master_profile_id', $master->id)->delete();
            foreach ($data['hours'] as $hour) {
                DB::table('master_working_hours')->insert([
                    'weekday' => (int) $hour['weekday'],
                    'starts_at' => $hour['starts_at'],
                    'ends_at' => $hour['ends_at'],
                    'master_profile_id' => $master->id,
                    'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Расписание сохранено.');
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
            $appointment->update($data);
        });

        app(WhatsApp::class)->confirmed($appointment->fresh());

        return back()->with('success', 'Статус записи изменён.');
    }
}
