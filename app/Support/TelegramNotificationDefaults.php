<?php
namespace App\Support;
final class TelegramNotificationDefaults
{
    public const VALUES = [
        'client_24h_enabled' => true,
        'client_24h_minutes' => 1440,
        'client_2h_enabled' => true,
        'client_2h_minutes' => 120,
        'master_30m_enabled' => true,
        'master_30m_minutes' => 30,
        'master_digest_enabled' => true,
        'master_digest_time' => '20:00',
    ];
}
