<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProfile extends Model
{
    protected $fillable = ['user_id', 'slug', 'display_name', 'specialty', 'bio', 'phone', 'address', 'timezone', 'booking_enabled', 'auto_confirm'];

    protected $casts = ['booking_enabled' => 'boolean', 'auto_confirm' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(MasterService::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(MasterAppointment::class);
    }
}
