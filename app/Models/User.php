<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'department',
    'staff_number',
    'cas_subject',
    'identity_source',
    'retreat_eligible',
    'directory_email',
    'mobile',
    'cas_synced_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            if ($user->role === 'admin' && Schema::hasTable('retreat_role_assignments')) {
                $user->roleAssignments()->firstOrCreate(
                    ['role' => RetreatRoleAssignment::ADMIN],
                    ['active' => true],
                );
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'retreat_eligible' => 'boolean',
            'cas_synced_at' => 'datetime',
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }

    public function retreatRoutes(): HasMany
    {
        return $this->hasMany(RetreatRoute::class, 'creator_id');
    }

    public function retreatGroups(): HasMany
    {
        return $this->hasMany(RetreatGroup::class, 'leader_id');
    }

    public function retreatGroupReviews(): HasMany
    {
        return $this->hasMany(RetreatGroupReview::class);
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RetreatRoleAssignment::class);
    }

    public function isRetreatAdmin(): bool
    {
        return $this->hasActiveRetreatRole(RetreatRoleAssignment::ADMIN);
    }

    public function canApproveRetreat(): bool
    {
        return in_array($this->role, ['department_approver', 'union_approver'], true);
    }

    public function isGroupDepartmentReviewer(?string $department = null): bool
    {
        if (! $this->retreat_eligible) {
            return false;
        }

        $department ??= $this->department;

        if (! $department || $department !== $this->department) {
            return false;
        }

        return $this->roleAssignments()
            ->where('role', RetreatRoleAssignment::DEPARTMENT_REVIEWER)
            ->where('active', true)
            ->where('scope_department', $department)
            ->exists();
    }

    public function isGroupFinalReviewer(): bool
    {
        return $this->retreat_eligible
            && $this->hasActiveRetreatRole(RetreatRoleAssignment::FINAL_REVIEWER);
    }

    public function canApproveGroup(): bool
    {
        return $this->isGroupFinalReviewer() || $this->isGroupDepartmentReviewer();
    }

    public function hasActiveRetreatRole(string $role): bool
    {
        return $this->roleAssignments()
            ->where('role', $role)
            ->where('active', true)
            ->exists();
    }

    /** @return array<int, string> */
    public function activeRetreatRoles(): array
    {
        return $this->roleAssignments()
            ->where('active', true)
            ->get()
            ->filter(fn (RetreatRoleAssignment $assignment) => $assignment->role !== RetreatRoleAssignment::DEPARTMENT_REVIEWER
                || $assignment->scope_department === $this->department)
            ->pluck('role')
            ->values()
            ->all();
    }
}
