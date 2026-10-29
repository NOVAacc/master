<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProfile extends Model
{
    protected $fillable = ['user_id', 'slug', 'display_name', 'specialty', 'bio', 'phone', 'address', 'address_url', 'timezone', 'booking_enabled', 'onboarding_industry_done', 'show_busy_slots', 'auto_confirm', 'whatsapp_notifications', 'avatar_path', 'cover_path', 'work1_path', 'work2_path', 'work3_path', 'tagline', 'instagram_username', 'profile_theme', 'story_style', 'story_template', 'is_demo', 'industry', 'activity'];

    protected $casts = ['is_demo' => 'boolean', 'booking_enabled' => 'boolean', 'onboarding_industry_done' => 'boolean', 'show_busy_slots' => 'boolean', 'auto_confirm' => 'boolean', 'whatsapp_notifications' => 'boolean'];

    protected $hidden = ['avatar_path', 'cover_path', 'work1_path', 'work2_path', 'work3_path'];

    protected $appends = ['avatar_url', 'cover_url', 'instagram_url', 'work1_url', 'work2_url', 'work3_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? route('master.image', ['master' => $this->slug, 'kind' => 'avatar', 'version' => pathinfo($this->avatar_path, PATHINFO_FILENAME)]) : null;
    }

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_path ? route('master.image', ['master' => $this->slug, 'kind' => 'cover', 'version' => pathinfo($this->cover_path, PATHINFO_FILENAME)]) : null;
    }

    public function getWork1UrlAttribute(): ?string
    {
        return $this->work1_path ? route('master.image', ['master' => $this->slug, 'kind' => 'work1', 'version' => pathinfo($this->work1_path, PATHINFO_FILENAME)]) : null;
    }

    public function getWork2UrlAttribute(): ?string
    {
        return $this->work2_path ? route('master.image', ['master' => $this->slug, 'kind' => 'work2', 'version' => pathinfo($this->work2_path, PATHINFO_FILENAME)]) : null;
    }

    public function getWork3UrlAttribute(): ?string
    {
        return $this->work3_path ? route('master.image', ['master' => $this->slug, 'kind' => 'work3', 'version' => pathinfo($this->work3_path, PATHINFO_FILENAME)]) : null;
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
