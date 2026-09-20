<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatGroupReview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'route_score' => 'integer',
            'meal_score' => 'integer',
            'attraction_score' => 'integer',
            'accommodation_score' => 'integer',
            'service_score' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RetreatGroup::class, 'retreat_group_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(RetreatRoute::class, 'retreat_route_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function overallScore(): float
    {
        return round(collect([
            $this->route_score,
            $this->meal_score,
            $this->attraction_score,
            $this->accommodation_score,
            $this->service_score,
        ])->average(), 1);
    }
}
