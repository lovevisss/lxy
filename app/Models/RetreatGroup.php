<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetreatGroup extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'application_deadline' => 'date',
            'formed_at' => 'datetime',
            'failed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'final_confirmation_deadline' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(RetreatRoute::class, 'retreat_route_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(RetreatGroupApplication::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(RetreatGroupReview::class);
    }

    public function smsNotifications(): HasMany
    {
        return $this->hasMany(RetreatSmsNotification::class);
    }
}
