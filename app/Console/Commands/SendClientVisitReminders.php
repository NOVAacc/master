<?php

namespace App\Console\Commands;

use App\Models\MasterAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendClientVisitReminders extends Command
{
    protected $signature = 'bosh:send-client-reminders {--dry-run : Count eligible reminders without sending}';
    protected $description = 'Send an at-most-once Telegram reminder roughly one hour before a confirmed visit';

    public function handle(): int
    {
        if (!config('services.telegram.bot_token')) {
            $this->warn('Telegram bot is not configured. No reminders sent.');
            return self::SUCCESS;
        }

        // A one-minute cron sends near the exact T-60 minute boundary.
        // The 10-minute backfill window tolerates short cron outages.
        $now = CarbonImmutable::now('UTC');
        $from = $now->addMinutes(50);
        $to = $now->addMinutes(60);

        $candidates = MasterAppointment::query()
            ->where('status', 'confirmed')
            ->whereBetween('starts_at', [$from, $to])
            ->where(function ($query) {
                $query->whereNotNull('client_telegram_chat_id')->orWhereNotNull('client_account_id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('client_visit_reminders as r')
                    ->whereColumn('r.master_appointment_id', 'master_appointments.id')
                    ->whereColumn('r.revision', 'master_appointments.revision');
            })
            ->with('masterProfile')
            ->orderBy('starts_at')->limit(300)->get();

        if ($this->option('dry-run')) {
            $this->info('Eligible appointments: '.$candidates->count());
            return self::SUCCESS;
        }

        foreach ($candidates as $appointment) {
            // A unique (appointment, revision) claim prevents overlapping
            // cron workers from sending duplicate reminders.
            $claimed = DB::table('client_visit_reminders')->insertOrIgnore([
                'master_appointment_id' => $appointment->id,
                'revision' => $appointment->revision,
                'visit_starts_at' => $appointment->starts_at->toDateTimeString(),
                'status' => 'claimed',
                'attempted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if (!$claimed) continue;

            $reminder = DB::table('client_visit_reminders')
                ->where('master_appointment_id', $appointment->id)
                ->where('revision', $appointment->revision)->first();
            if (!$reminder) continue;

            // Recheck after claiming, in case the master just moved/cancelled
            // the appointment or the customer opted out.
            $fresh = MasterAppointment::with('masterProfile')->find($appointment->id);
            if (!$fresh || $fresh->status !== 'confirmed' || $fresh->revision !== $appointment->revision
                || !$fresh->starts_at->equalTo($appointment->starts_at)) {
                $this->setStatus($reminder->id, 'obsolete');
                continue;
            }

            $chatId = $fresh->client_telegram_chat_id;
            if ($fresh->client_account_id) {
                $account = DB::table('client_accounts')->where('id', $fresh->client_account_id)->first();
                $chatId = $account && $account->notifications_enabled ? $account->telegram_chat_id : null;
            }
            if (!$chatId) {
                $this->setStatus($reminder->id, 'opted_out');
                continue;
            }

            $master = $fresh->masterProfile;
            $time = $fresh->starts_at->copy()->setTimezone($master->timezone)->format('d.m.Y, H:i');
            $text = "BOSH · Напоминание о записи\n";
            $text .= "До вашего визита примерно 1 час.\n";
            $text .= "Мастер: {$master->display_name}\n";
            $text .= "Услуга: {$fresh->service_name_snapshot}\n";
            $text .= "Дата и время: {$time}\n";
            $text .= 'Стоимость: '.number_format((int) $fresh->price_som, 0, '.', ' ')." сом\n";
            if ($master->address) $text .= "Адрес: {$master->address}\n";
            $text .= "Детали записи: ".app(\App\Services\ClientBookingAlerts::class)->manageUrl($fresh);

            // At-most-once send attempt: a network timeout is recorded as
            // failed, not blindly retried (Telegram may have delivered it).
            try {
                $response = Http::connectTimeout(2)->timeout(8)
                    ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $text,
                        'disable_web_page_preview' => true,
                    ]);
                if ($response->successful() && $response->json('ok') === true) {
                    $this->setStatus($reminder->id, 'sent');
                } else {
                    $this->setStatus($reminder->id, 'failed');
                    Log::warning('BOSH client visit reminder rejected', [
                        'appointment_id' => $fresh->id, 'http_status' => $response->status(),
                    ]);
                }
            } catch (\Throwable $e) {
                $this->setStatus($reminder->id, 'failed');
                Log::warning('BOSH client visit reminder failed', [
                    'appointment_id' => $fresh->id, 'exception' => get_class($e),
                ]);
            }
        }

        return self::SUCCESS;
    }

    private function setStatus(int $id, string $status): void
    {
        DB::table('client_visit_reminders')->where('id', $id)->update([
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'updated_at' => now(),
        ]);
    }
}
