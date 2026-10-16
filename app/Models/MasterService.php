<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterService extends Model
{
    protected $fillable = ['master_profile_id', 'name', 'description', 'duration_minutes', 'price_som', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean', 'duration_minutes' => 'integer', 'price_som' => 'integer'];

    public function masterProfile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class);
    }
}
