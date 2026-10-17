<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterAppointment extends Model
{
    protected $attributes = ['revision' => 1, 'notification_revision' => 1];

    protected $fillable = ['master_profile_id', 'master_service_id', 'client_name', 'client_phone', 'client_comment', 'starts_at', 'ends_at', 'price_som', 'service_name_snapshot', 'services_snapshot', 'whatsapp_consent', 'status'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'price_som' => 'integer', 'services_snapshot' => 'array', 'whatsapp_consent' => 'boolean', 'revision' => 'integer', 'notification_revision' => 'integer'];

    public function changes(): HasMany
    {
        return $this->hasMany(MasterAppointmentChange::class)->orderByDesc('id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(MasterNotification::class);
    }

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MasterService::class, 'master_service_id');
    }
}
