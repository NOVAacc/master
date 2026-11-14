<?php

namespace App\Services;

use App\Models\MasterAppointment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

final class ClientBookingAlerts
{
    public function manageUrl(MasterAppointment $appointment): string
    {
        return URL::temporarySignedRoute('booking.manage', now()->addDays(30), [
            'appointment' => $appointment->id,
            'revision' => $appointment->revision,
        ]);
    }

    public function record(MasterAppointment $appointment, string $kind, ?string $previousStart = null): void
    {
        try {
            $master = $appointment->masterProfile;
            $start = $appointment->starts_at->copy()->setTimezone($master->timezone)->format('d.m.Y H:i');
            $old = $previousStart
                ? CarbonImmutable::parse($previousStart)->setTimezone($master->timezone)->format('d.m.Y H:i')
                : null;
            $label = match ($kind) {
                'cancelled' => 'Ваша запись отменена.',
                'confirmed' => 'Ваша запись подтверждена.',
                default => 'Ваша запись изменена.',
            };
            $text = "BOSH · {$label}\nМастер: {$master->display_name}\nУслуга: {$appointment->service_name_snapshot}\n";
            if ($old && $old !== $start) $text .= "Было: {$old}\n";
            $text .= ($kind === 'cancelled' ? "Дата отменённой записи: " : "Новое время: ").$start."\n";
            $text .= 'Стоимость: '.number_format((int) $appointment->price_som, 0, '.', ' ')." сом\n";
            $text .= "Актуальная ссылка на запись:\n".$this->manageUrl($appointment);

            $created = DB::table('client_booking_alerts')->insertOrIgnore([
                'master_appointment_id' => $appointment->id, 'revision' => $appointment->revision,
                'kind' => $kind, 'message' => $text, 'delivery_status' => 'pending_manual',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if (!$created || !$appointment->client_telegram_chat_id || !config('services.telegram.bot_token')) return;
            $alert = DB::table('client_booking_alerts')
                ->where('master_appointment_id', $appointment->id)
                ->where('revision', $appointment->revision)->where('kind', $kind)->first();
            try {
                $response = Http::connectTimeout(2)->timeout(6)
                    ->post('https://api.telegram.org/bot'.config('services.telegram.bot_token').'/sendMessage', [
                        'chat_id' => $appointment->client_telegram_chat_id,
                        'text' => $text, 'disable_web_page_preview' => true,
                    ]);
                if ($response->successful() && $response->json('ok') === true) {
                    DB::table('client_booking_alerts')->where('id', $alert->id)
                        ->update(['delivery_status' => 'sent', 'sent_at' => now(), 'updated_at' => now()]);
                } else {
                    DB::table('client_booking_alerts')->where('id', $alert->id)
                        ->update(['delivery_status' => 'failed', 'updated_at' => now()]);
                    Log::warning('Client Telegram alert rejected', ['appointment_id' => $appointment->id, 'status' => $response->status()]);
                }
            } catch (\Throwable $e) {
                DB::table('client_booking_alerts')->where('id', $alert->id)
                    ->update(['delivery_status' => 'failed', 'updated_at' => now()]);
                Log::warning('Client Telegram alert failed', ['appointment_id' => $appointment->id, 'exception' => get_class($e)]);
            }
        } catch (\Throwable $e) {
            // Never break a successful appointment mutation because of notifications.
            Log::warning('Client booking alert failure', ['appointment_id' => $appointment->id, 'exception' => get_class($e)]);
        }
    }
}
