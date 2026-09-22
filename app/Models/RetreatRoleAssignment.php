<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetreatRoleAssignment extends Model
{
    public const ADMIN = 'admin';

    public const DEPARTMENT_REVIEWER = 'group_department_reviewer';

    public const FINAL_REVIEWER = 'group_final_reviewer';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
