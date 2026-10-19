<?php

namespace App\Http\Controllers;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Services\WhatsApp;
use App\Services\WorkingHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class CustomerBookingController extends Controller
{
    private function checkAccess(MasterAppointment $appointment, int $revision): void
    {
        // The signed link is a bearer capability. Revoking the current revision
        // immediately invalidates old links after a master/client change.
        abort_unless($appointment->revision === $revision, 404);
    }

    private function allowChange(MasterAppointment $appointment): void
    {
        if (! in_array($appointment->status, ['pending', 'confirmed'], true)
            || $appointment->starts_at->lessThanOrEqualTo(CarbonImmutable::now('UTC')->addHours(2))) {
            throw ValidationException::withMessages([
                'booking' => 'Изменить запись можно не позднее чем за 2 часа до начала.',
            ]);
        }
    }

    public function show(Request $request, int $appointment)
    {
        $appointment = MasterAppointment::findOrFail($appointment);
        $this->checkAccess($appointment, (int) $request->query('revision'));
        $master = $appointment->masterProfile;
        return Inertia::render('ManageBooking', [
            'appointment' => $appointment->only([
                'id', 'status', 'starts_at', 'ends_at', 'service_name_snapshot',
                'price_som', 'customer_reschedule_count',
            ]),
            'master' => $master->only(['slug', 'display_name', 'timezone']),
            'canChange' => in_array($appointment->status, ['pending', 'confirmed'], true)
                && $appointment->starts_at->greaterThan(CarbonImmutable::now('UTC')->addHours(2)),
            'actionUrl' => $request->fullUrl(),
        ]);
    }

    public function update(Request $request, int $appointment)
    {
        $appointment = MasterAppointment::findOrFail($appointment);
        $this->checkAccess($appointment, (int) $request->query('revision'));
        $data = $request->validate([
            'action' => ['required', 'in:cancel,reschedule'],
            'starts_at_local' => ['required_if:action,reschedule', 'nullable', 'date_format:Y-m-d\\TH:i'],
        ]);
        $changed = DB::transaction(function () use ($appointment, $data, $request): MasterAppointment {
            $master = MasterProfile::whereKey($appointment->master_profile_id)->lockForUpdate()->firstOrFail();
            $a = MasterAppointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $this->checkAccess($a, (int) $request->query('revision'));
            $this->allowChange($a);
            if ($data['action'] === 'cancel') {
                $a->status = 'cancelled';
                $a->attendance_status = 'not_applicable';
            } else {
                if ($a->customer_reschedule_count >= 2) {
                    throw ValidationException::withMessages(['booking' => 'Достигнут лимит переносов. Свяжитесь с мастером.']);
                }
                $local = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at_local'], $master->timezone);
                if (! $local || $local->format('Y-m-d\\TH:i') !== $data['starts_at_local']
                    || ! in_array((int) $local->minute, [0, 30], true)) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Выберите время с шагом 30 минут.']);
                }
                $start = $local->utc();
                $duration = (int) $a->starts_at->diffInMinutes($a->ends_at);
                $end = $start->addMinutes($duration);
                if ($start->lessThanOrEqualTo(CarbonImmutable::now('UTC')->addHours(2))
                    || $start->greaterThan(CarbonImmutable::now('UTC')->addDays(90))
                    || ! $master->booking_enabled || $master->is_demo
                    || ! (new WorkingHours)->contains($master, $start, $end)) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Выбранное время недоступно.']);
                }
                $busy = $master->appointments()->whereKeyNot($a->id)->whereIn('status', ['pending', 'confirmed'])
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $blocked = DB::table('master_time_blocks')->where('master_profile_id', $master->id)
                    ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                $groupBusy = DB::table('master_group_sessions')->where('master_profile_id', $master->id)
                    ->where('status', 'open')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists();
                if ($busy || $blocked || $groupBusy) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Интервал занят. Выберите другое время.']);
                }
                if ($start->equalTo($a->starts_at)) {
                    throw ValidationException::withMessages(['starts_at_local' => 'Выберите другое время.']);
                }
                $a->starts_at = $start;
                $a->ends_at = $end;
                $a->customer_reschedule_count++;
                $a->notification_revision++;
            }
            $a->revision++;
            $a->save();
            return $a;
        }, 3);

        // Never send a new confirmation for a cancelled booking.
        if ($changed->status === 'confirmed') {
            app(WhatsApp::class)->confirmed($changed);
        }
        $freshLink = URL::temporarySignedRoute('booking.manage', now()->addDays(30), [
            'appointment' => $changed->id,
            'revision' => $changed->revision,
        ]);
        return redirect()->to($freshLink)->with('success', $data['action'] === 'cancel'
            ? 'Запись отменена.' : 'Время записи изменено. Сохраните эту обновлённую ссылку.');
    }
}
