<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProfile extends Model
{
    protected $fillable = ['user_id', 'slug', 'display_name', 'specialty', 'bio', 'phone', 'address', 'timezone', 'booking_enabled', 'auto_confirm', 'whatsapp_notifications', 'avatar_path', 'cover_path', 'tagline', 'instagram_username', 'profile_theme'];

    protected $casts = ['booking_enabled' => 'boolean', 'auto_confirm' => 'boolean', 'whatsapp_notifications' => 'boolean'];

    protected $hidden = ['avatar_path', 'cover_path'];

    protected $appends = ['avatar_url', 'cover_url', 'instagram_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? route('master.image', ['master' => $this->slug, 'kind' => 'avatar', 'version' => pathinfo($this->avatar_path, PATHINFO_FILENAME)]) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? route('master.image', ['master' => $this->slug, 'kind' => 'cover', 'version' => pathinfo($this->cover_path, PATHINFO_FILENAME)]) : null;
    }

    public function getInstagramUrlAttribute(): ?string
    {
        return $this->instagram_username ? 'https://www.instagram.com/'.rawurlencode($this->instagram_username).'/' : null;
    }

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
