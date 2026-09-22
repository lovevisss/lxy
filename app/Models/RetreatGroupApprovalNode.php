<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatGroupApprovalNode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'member_snapshot' => 'array',
            'active' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RetreatGroup::class, 'retreat_group_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
