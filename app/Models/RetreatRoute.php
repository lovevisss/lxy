<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetreatRoute extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'value_added' => 'array',
            'self_funded_items' => 'array',
            'notices' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(RetreatRouteImport::class, 'retreat_route_import_id');
    }

    public function itineraryDays(): HasMany
    {
        return $this->hasMany(RetreatItineraryDay::class)->orderBy('day_number');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(RetreatRouteApproval::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(RetreatGroup::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RetreatGroupReview::class);
    }
}
