<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotSlotOfferSlot extends Model
{
    protected $fillable = ['hot_slot_offer_id', 'starts_at', 'ends_at', 'master_appointment_id'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function offer(): BelongsTo { return $this->belongsTo(HotSlotOffer::class, 'hot_slot_offer_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(MasterAppointment::class, 'master_appointment_id'); }
}
