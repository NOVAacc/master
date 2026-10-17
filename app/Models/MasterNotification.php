<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterNotification extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['phone', 'provider_id'];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(MasterAppointment::class, 'master_appointment_id');
    }
}
