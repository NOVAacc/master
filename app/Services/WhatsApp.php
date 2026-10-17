<?php

namespace App\Services;

use App\Models\MasterAppointment;
use App\Models\MasterNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

final class WhatsApp
{
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone ?? '');
        if (strlen($digits) === 9) {
            $digits = '996'.$digits;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '996'.substr($digits, 1);
        }

        return preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? $digits : null;
    }

    public function configured(): bool
    {
        $c = config('services.whatsapp');

        return (bool) ($c['enabled'] && $c['token'] && preg_match('/^[0-9]+$/', $c['phone_number_id'] ?? '') && preg_match('/^v[0-9]+\.0$/', $c['version'] ?? '') && $c['client_template'] && $c['master_template']);
    }

    public function message(MasterAppointment $a, string $recipient): string
    {
        $master = $a->masterProfile;
        $start = $a->starts_at->copy()->setTimezone($master->timezone);
        $end = $a->ends_at->copy()->setTimezone($master->timezone);
        $services = collect($a->services_snapshot)->pluck('name')->implode(' + ') ?: $a->service_name_snapshot;
        $state = $a->status === 'confirmed' ? ($a->notification_revision > 1 ? 'Запись изменена и подтверждена' : 'Запись подтверждена') : 'Заявка на запись';
        $lines = ["BOSH · {$state} №{$a->id}", "Мастер: {$master->display_name}", "Клиент: {$a->client_name}"];
        if ($recipient === 'master') {
            $lines[] = 'Телефон клиента: '.$a->client_phone;
        }
        $lines[] = 'Услуги: '.$services;
        $lines[] = 'Дата и время: '.$start->format('d.m.Y H:i').'–'.$end->format('H:i').' ('.$master->timezone.')';
        $lines[] = 'Стоимость: '.number_format($a->price_som, 0, '.', ' ').' сом';
        if ($master->address) {
            $lines[] = 'Адрес: '.$master->address;
        }
        if ($recipient === 'client' && $master->phone) {
            $lines[] = 'Телефон мастера: '.$master->phone;
        }

        return implode("\n", $lines);
    }

    public function link(?string $phone, string $text): ?string
    {
        $number = self::phone($phone);

        return $number ? 'https://wa.me/'.$number.'?text='.rawurlencode($text) : null;
    }

    public function confirmed(MasterAppointment $appointment): void
    {
        if ($appointment->status !== 'confirmed' || $appointment->starts_at->isPast()) {
            return;
        }
        $master = $appointment->masterProfile;
        foreach (['client' => $appointment->whatsapp_consent, 'master' => $master->whatsapp_notifications] as $recipient => $consent) {
            if (! $consent) {
                continue;
            }
            $phone = self::phone($recipient === 'client' ? $appointment->client_phone : $master->phone);
            if (! $phone) {
                continue;
            }
            $notification = MasterNotification::firstOrCreate(['master_appointment_id' => $appointment->id, 'recipient' => $recipient, 'revision' => $appointment->notification_revision], ['phone' => $phone]);
            $this->send($notification);
        }
    }

    public function send(MasterNotification $notification): void
    {
        if (! $this->configured()) {
            return;
        }
        $appointment = $notification->appointment;
        if ($appointment->status !== 'confirmed' || $appointment->starts_at->isPast()) {
            return;
        }
        if ((int) $notification->revision !== (int) $appointment->notification_revision) {
            return;
        }
        $master = $appointment->masterProfile;
        if (! ($notification->recipient === 'client' ? $appointment->whatsapp_consent : $master->whatsapp_notifications)) {
            return;
        }
        $currentPhone = self::phone($notification->recipient === 'client' ? $appointment->client_phone : $master->phone);
        if ($currentPhone !== $notification->phone) {
            $notification->update(['status' => 'recipient_changed', 'error_code' => 'recipient_changed']);

            return;
        }
        // Atomic claim prevents parallel confirmations and retries from sending duplicates.
        if (! MasterNotification::whereKey($notification->id)->whereIn('status', ['not_configured', 'failed'])
            ->where('attempts', '<', 5)->update(['status' => 'sending', 'attempts' => DB::raw('attempts + 1'), 'error_code' => null])) {
            return;
        }
        try {
            $c = config('services.whatsapp');
            $start = $appointment->starts_at->copy()->setTimezone($master->timezone);
            $end = $appointment->ends_at->copy()->setTimezone($master->timezone);
            $values = [$appointment->client_name, $master->display_name, $appointment->service_name_snapshot,
                $start->format('d.m.Y'), $start->format('H:i').'–'.$end->format('H:i').' ('.$master->timezone.')',
                number_format($appointment->price_som, 0, '.', ' ').' сом', $master->address ?: 'Уточните у мастера',
                $notification->recipient === 'client' ? ($master->phone ?: 'Связь через страницу мастера') : $appointment->client_phone];
            $response = Http::withToken($c['token'])->acceptJson()->connectTimeout(3)->timeout(8)
                ->post('https://graph.facebook.com/'.$c['version'].'/'.$c['phone_number_id'].'/messages', [
                    'messaging_product' => 'whatsapp', 'to' => $notification->phone, 'type' => 'template',
                    'template' => ['name' => $c[$notification->recipient.'_template'], 'language' => ['code' => $c['language']],
                        'components' => [['type' => 'body', 'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => preg_replace('/\s+/u', ' ', $value)], $values)]]],
                ]);
            if ($response->successful() && is_string($response->json('messages.0.id'))) {
                $notification->update(['status' => 'accepted', 'provider_id' => $response->json('messages.0.id'), 'error_code' => null]);
            } else {
                $notification->update(['status' => $response->serverError() || $response->successful() ? 'unknown' : 'failed', 'error_code' => 'http_'.$response->status()]);
            }
        } catch (Throwable $error) {
            // A timeout can occur after Meta accepts a message. Do not blindly resend.
            $notification->update(['status' => 'unknown', 'error_code' => 'connection_unknown']);
        }
    }
}
