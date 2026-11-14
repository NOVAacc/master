<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterProfile extends Model
{
    protected $fillable = ['user_id', 'slug', 'display_name', 'specialty', 'bio', 'phone', 'address', 'address_url', 'timezone', 'booking_enabled', 'onboarding_industry_done', 'onboarding_restart_step', 'show_busy_slots', 'auto_confirm', 'whatsapp_notifications', 'avatar_path', 'cover_path', 'tagline', 'instagram_username', 'profile_theme', 'story_style', 'story_template', 'is_demo', 'industry', 'activity'];

    protected $casts = ['is_demo' => 'boolean', 'booking_enabled' => 'boolean', 'onboarding_industry_done' => 'boolean', 'onboarding_restart_step' => 'integer', 'show_busy_slots' => 'boolean', 'auto_confirm' => 'boolean', 'whatsapp_notifications' => 'boolean'];

    protected $hidden = ['telegram_chat_id', 'telegram_link_hash', 'telegram_link_expires_at', 'avatar_path', 'cover_path', 'work1_path', 'work2_path', 'work3_path'];

    protected $appends = ['avatar_url', 'cover_url', 'instagram_url', ];

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

    public function hotSlotOffers(): HasMany
    {
        return $this->hasMany(HotSlotOffer::class);
    }
}
