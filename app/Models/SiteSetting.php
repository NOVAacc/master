<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function notificationSettings(): array
    {
        $defaults = \App\Support\TelegramNotificationDefaults::VALUES;
        $stored = static::query()->whereIn('key', array_map(fn ($key) => 'telegram_'.$key, array_keys($defaults)))
            ->pluck('value', 'key');
        foreach ($defaults as $key => $default) {
            $raw = $stored->get('telegram_'.$key);
            if ($raw === null) continue;
            $defaults[$key] = is_bool($default) ? $raw === '1' : (is_int($default) ? (int) $raw : $raw);
        }
        return $defaults;
    }

    public static function instagramUsername(): string
    {
        return static::whereKey('instagram_username')->value('value') ?? 'bosh.kgz';
    }
}
