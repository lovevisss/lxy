<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatGroupApprovalEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RetreatGroup::class, 'retreat_group_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(RetreatGroupApprovalNode::class, 'approval_node_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
