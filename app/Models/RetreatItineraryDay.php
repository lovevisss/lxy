<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatItineraryDay extends Model
{
    protected $guarded = [];

    public function route(): BelongsTo
    {
        return $this->belongsTo(RetreatRoute::class, 'retreat_route_id');
    }
}
