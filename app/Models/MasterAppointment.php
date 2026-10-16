<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterAppointment extends Model
{
    protected $fillable = ['master_profile_id', 'master_service_id', 'client_name', 'client_phone', 'client_comment', 'starts_at', 'ends_at', 'price_som', 'service_name_snapshot', 'status'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'price_som' => 'integer'];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(MasterService::class, 'master_service_id');
    }
}
