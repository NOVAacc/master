<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function instagramUsername(): string
    {
        return static::whereKey('instagram_username')->value('value') ?? 'bosh.kgz';
    }
}
