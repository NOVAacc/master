<?php
namespace App\Console\Commands;

use App\Models\MasterAppointment;
use App\Models\MasterProfile;
use App\Models\SiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SendScheduledTelegramNotifications extends Command
{
    protected $signature = 'bosh:send-scheduled-telegram {--dry-run : Count eligible items without sending}';
    protected $description = 'Send configurable client and master Telegram reminders and a daily master summary';

    public function handle(): int
    {
        if (!config('services.telegram.bot_token')) {
            $this->warn('Telegram bot is not configured.');
            return self::SUCCESS;
        }

        $settings = SiteSetting::notificationSettings();
        $now = CarbonImmutable::now('UTC');
        $count = 0;
        foreach ([
            ['client_24h', 'client_24h_enabled', 'client_24h_minutes'],
            ['client_2h', 'client_2h_enabled', 'client_2h_minutes'],
            ['master_30m', 'master_30m_enabled', 'master_30m_minutes'],
        ] as [$kind, $enabled, $offset]) {
            if (!$settings[$enabled]) continue;
            $minutes = (int) $settings[$offset];
            $appointments = MasterAppointment::query()->with('masterProfile')
                ->where('status', 'confirmed')
                ->whereBetween('starts_at', [
                    $now->addMinutes($minutes - 10)->toDateTimeString(),
                    $now->addMinutes($minutes)->toDateTimeString(),
                ])
                ->orderBy('starts_at')->limit(500)->get();

            foreach ($appointments as $appointment) {
                $due = CarbonImmutable::parse($appointment->starts_at, 'UTC')->subMinutes($minutes);
                // Never send an interval reminder for bookings made after its due time.
                if (CarbonImmutable::parse($appointment->created_at, 'UTC')->greaterThan($due)) continue;
                $master = $appointment->masterProfile;
                if (!$master || $master->is_demo) continue;
                $chatId = $kind === 'master_30m' ? ($master->telegram_alerts_enabled ? $master->telegram_chat_id : null) : $this->clientChatId($appointment);
                if (!$chatId) continue;
                $key = $this->appointmentKey($kind, $appointment);
                if (DB::table('telegram_scheduled_notifications')->where('event_key', $key)->exists()) continue;
                $count++;
                if ($this->option('dry-run')) continue;
                if (!$this->claim($key, $kind, $appointment->id, $master->id)) continue;
                $fresh = MasterAppointment::with('masterProfile')->find($appointment->id);
                if (!$fresh || $fresh->status !== 'confirmed' || !$fresh->starts_at->equalTo($appointment->starts_at)
                    || !$fresh->masterProfile || $fresh->masterProfile->is_demo) {
                    $this->mark($key, 'obsolete');
                    continue;
                }
                $chatId = $kind === 'master_30m'
                    ? ($fresh->masterProfile->telegram_alerts_enabled ? $fresh->masterProfile->telegram_chat_id : null)
                    : $this->clientChatId($fresh);
                if (!$chatId) {
                    $this->mark($key, 'opted_out');
                    continue;
                }
                $this->deliver($key, $chatId, $this->appointmentMessage($kind, $fresh, $minutes));
            }
        }

        if ($settings['master_digest_enabled']) {
            $count += $this->dailySummaries($now, $settings['master_digest_time']);
        }
        $this->info('Eligible notifications: '.$count);
        return self::SUCCESS;
    }

    private function clientChatId(MasterAppointment $a): ?string
    {
        if ($a->client_account_id) {
            $account = DB::table('client_accounts')->where('id', $a->client_account_id)->first();
            return $account && $account->notifications_enabled ? (string) $account->telegram_chat_id : null;
        }
        return $a->client_telegram_chat_id ? (string) $a->client_telegram_chat_id : null;
    }

    private function appointmentKey(string $kind, MasterAppointment $a): string
    {
        // Time identity, not revision: editing price or name cannot resend a reminder.
        return hash('sha256', $kind.'|'.$a->id.'|'.$a->starts_at->copy()->utc()->format('Y-m-d H:i:s'));
    }

    private function claim(string $key, string $kind, ?int $appointmentId, int $masterId): bool
    {
        return DB::table('telegram_scheduled_notifications')->insertOrIgnore([
            'event_key' => $key, 'kind' => $kind,
            'master_appointment_id' => $appointmentId,
            'master_profile_id' => $masterId,
            'status' => 'claimed', 'created_at' => now(), 'updated_at' => now(),
        ]) === 1;
    }

    private function mark(string $key, string $status): void
    {
        DB::table('telegram_scheduled_notifications')->where('event_key', $key)->update([
            'status' => $status, 'sent_at' => $status === 'sent' ? now() : null, 'updated_at' => now(),
        ]);
    }

    private function deliver(string $key, string $chatId, string $message): void
    {
        try {
            $r = Http::connectTimeout(2)->timeout(8)
                ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                    'chat_id' => $chatId, 'text' => $message, 'disable_web_page_preview' => true,
                ]);
            $ok = $r->successful() && $r->json('ok') === true;
            $this->mark($key, $ok ? 'sent' : 'failed');
            if (!$ok) Log::warning('BOSH scheduled Telegram rejected', ['kind_key' => $key, 'http_status' => $r->status()]);
        } catch (\Throwable $e) {
            // Do not automatically retry uncertain delivery: Telegram may have accepted it.
            $this->mark($key, 'failed');
            Log::warning('BOSH scheduled Telegram failed', ['kind_key' => $key, 'exception' => get_class($e)]);
        }
    }

    private function appointmentMessage(string $kind, MasterAppointment $a, int $minutes): string
    {
        $master = $a->masterProfile;
        $tz = $master->timezone ?: 'Asia/Bishkek';
        $when = $a->starts_at->copy()->setTimezone($tz)->format('d.m.Y, H:i');
        $interval = $minutes % 60 === 0 ? ($minutes / 60).' ч' : $minutes.' мин';
        if ($kind === 'master_30m') {
            return "BOSH · Скоро клиент!\nЧерез {$interval}: {$a->client_name}\nУслуга: {$a->service_name_snapshot}\nНачало: {$when}\nОткрыть BOSH: ".route('dashboard');
        }
        $text = "BOSH · Напоминание о записи\nДо визита: {$interval}\nМастер: {$master->display_name}\nУслуга: {$a->service_name_snapshot}\nДата и время: {$when}\n";
        if ($master->address) $text .= "Адрес: {$master->address}\n";
        return $text.'Детали записи: '.app(\App\Services\ClientBookingAlerts::class)->manageUrl($a);
    }

    private function dailySummaries(CarbonImmutable $now, string $time): int
    {
        $count = 0;
        MasterProfile::query()->whereNotNull('telegram_chat_id')
            ->where('telegram_alerts_enabled', true)->where('is_demo', false)
            ->chunkById(100, function ($masters) use ($now, $time, &$count) {
                foreach ($masters as $master) {
                    try { $local = $now->setTimezone($master->timezone ?: 'Asia/Bishkek'); }
                    catch (\Throwable $e) { continue; }
                    $scheduled = $local->startOfDay()->setTimeFromTimeString($time);
                    if ($local->lessThan($scheduled) || $local->greaterThanOrEqualTo($scheduled->addMinutes(10))) continue;
                    $tomorrow = $local->addDay()->startOfDay();
                    $appointments = MasterAppointment::query()->where('master_profile_id', $master->id)
                        ->whereIn('status', ['pending', 'confirmed'])
                        ->where('starts_at', '>=', $tomorrow->utc()->toDateTimeString())
                        ->where('starts_at', '<', $tomorrow->addDay()->utc()->toDateTimeString())
                        ->orderBy('starts_at')->get();
                    if ($appointments->isEmpty()) continue;
                    $key = hash('sha256', 'master_digest|'.$master->id.'|'.$local->format('Y-m-d'));
                    if (DB::table('telegram_scheduled_notifications')->where('event_key', $key)->exists()) continue;
                    $count++;
                    if ($this->option('dry-run')) continue;
                    if (!$this->claim($key, 'master_digest', null, $master->id)) continue;
                    // Recheck opt-out immediately before sending.
                    $current = MasterProfile::find($master->id);
                    if (!$current || !$current->telegram_alerts_enabled || !$current->telegram_chat_id) {
                        $this->mark($key, 'opted_out');
                        continue;
                    }
                    $message = "BOSH · Записи на завтра (".$tomorrow->format('d.m').")\nВсего: ".$appointments->count()."\n";
                    foreach ($appointments->take(20) as $a) {
                        $message .= $a->starts_at->copy()->setTimezone($master->timezone ?: 'Asia/Bishkek')->format('H:i')
                            ." — {$a->client_name}, {$a->service_name_snapshot}\n";
                    }
                    if ($appointments->count() > 20) $message .= 'И ещё '.($appointments->count() - 20)." записей\n";
                    $message .= 'Календарь: '.route('dashboard');
                    $this->deliver($key, (string) $current->telegram_chat_id, $message);
                }
            });
        return $count;
    }
}
