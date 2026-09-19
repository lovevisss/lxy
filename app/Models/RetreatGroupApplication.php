<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatGroupApplication extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'family_members' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RetreatGroup::class, 'retreat_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
