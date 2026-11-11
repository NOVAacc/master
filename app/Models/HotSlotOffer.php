<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HotSlotOffer extends Model
{
    protected $fillable = ['master_profile_id', 'master_service_id', 'token', 'special_price_som', 'story_template', 'clicks', 'cancelled_at'];

    protected $casts = ['special_price_som' => 'integer', 'clicks' => 'integer', 'cancelled_at' => 'datetime'];

    public function masterProfile(): BelongsTo { return $this->belongsTo(MasterProfile::class); }
    public function service(): BelongsTo { return $this->belongsTo(MasterService::class, 'master_service_id'); }
    public function slots(): HasMany { return $this->hasMany(HotSlotOfferSlot::class); }
}
