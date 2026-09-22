<?php

namespace App\Http\Controllers;

use App\Models\RetreatGroupApprovalNode;
use App\Models\RetreatPermissionAudit;
use App\Models\RetreatRoleAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RetreatPermissionController extends Controller
{
    private const ROLES = [
        RetreatRoleAssignment::ADMIN,
        RetreatRoleAssignment::DEPARTMENT_REVIEWER,
        RetreatRoleAssignment::FINAL_REVIEWER,
    ];

    public function index(Request $request): Response
    {
        abort_unless($request->user()->isRetreatAdmin(), 403);

        $query = User::query()
            ->with(['roleAssignments' => fn ($query) => $query->whereIn('role', self::ROLES), 'roleAssignments.grantedBy'])
            ->where(function ($query): void {
                $query->where(fn ($teachers) => $teachers->where('identity_source', 'cas')->where('retreat_eligible', true))
                    ->orWhereHas('roleAssignments', fn ($roles) => $roles->whereIn('role', self::ROLES));
            });

        $search = trim((string) $request->query('search'));
        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('staff_number', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }
        if ($department = trim((string) $request->query('department'))) {
            $query->where('department', $department);
        }
        if ($role = trim((string) $request->query('role'))) {
            $query->whereHas('roleAssignments', fn ($roles) => $roles->where('role', $role)->where('active', true));
        }

        $users = $query->orderBy('department')->orderBy('name')->paginate(30)->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'staffNumber' => $user->staff_number,
                'department' => $user->department,
                'email' => $user->directory_email ?: $user->email,
                'roles' => $user->roleAssignments->where('active', true)->pluck('role')->values(),
                'departmentRoleStale' => $user->roleAssignments->contains(fn (RetreatRoleAssignment $assignment) => $assignment->active
                    && $assignment->role === RetreatRoleAssignment::DEPARTMENT_REVIEWER
                    && $assignment->scope_department !== $user->department),
                'configuredAt' => $user->roleAssignments->max('updated_at')?->format('Y-m-d H:i'),
                'configuredBy' => $user->roleAssignments->sortByDesc('updated_at')->first()?->grantedBy?->name,
            ]);

        $departments = User::query()->where('identity_source', 'cas')->where('retreat_eligible', true)->whereNotNull('department')
            ->distinct()->orderBy('department')->pluck('department');
        $coveredDepartments = RetreatRoleAssignment::query()
            ->where('role', RetreatRoleAssignment::DEPARTMENT_REVIEWER)
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('retreat_eligible', true)
                ->whereColumn('users.department', 'retreat_role_assignments.scope_department'))
            ->distinct()->pluck('scope_department');

        $audits = RetreatPermissionAudit::query()->with(['targetUser', 'actor'])->latest()
            ->paginate(30, ['*'], 'audit_page')->withQueryString()
            ->through(fn (RetreatPermissionAudit $audit) => [
                'id' => $audit->id,
                'action' => $audit->action,
                'role' => $audit->role,
                'department' => $audit->scope_department,
                'target' => $audit->target_name,
                'actor' => $audit->actor_name,
                'time' => $audit->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia::render('retreat/Permissions', [
            'users' => $users,
            'departments' => $departments,
            'filters' => ['search' => $search, 'department' => $department ?? '', 'role' => $role ?? ''],
            'stats' => [
                'admins' => $this->activeRoleCount(RetreatRoleAssignment::ADMIN),
                'departmentReviewers' => $this->activeRoleCount(RetreatRoleAssignment::DEPARTMENT_REVIEWER),
                'finalReviewers' => $this->activeRoleCount(RetreatRoleAssignment::FINAL_REVIEWER),
                'coveredDepartments' => $coveredDepartments->count(),
                'totalDepartments' => $departments->count(),
            ],
            'audits' => $audits,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isRetreatAdmin(), 403);
        $validated = $request->validate([
            'roles' => ['present', 'array'],
            'roles.*' => ['string', Rule::in(self::ROLES)],
        ]);
        $desired = collect($validated['roles'])->unique()->values();

        $existing = $user->roleAssignments()->where('active', true)->pluck('role');
        $newRoles = $desired->diff($existing);
        if ($newRoles->isNotEmpty()
            && ($user->identity_source !== 'cas' || ! $user->retreat_eligible || ! $user->staff_number)) {
            throw ValidationException::withMessages([
                'roles' => '只能从已同步且具备疗休养资格的 CAS 教师账号中新增授权。',
            ]);
        }

        if ($desired->contains(RetreatRoleAssignment::DEPARTMENT_REVIEWER) && ! $user->department) {
            throw ValidationException::withMessages(['roles' => '该人员没有所属单位，不能配置为分院审核人。']);
        }

        DB::transaction(function () use ($request, $user, $desired): void {
            $current = $user->roleAssignments()->whereIn('role', self::ROLES)->get()->keyBy('role');

            foreach (self::ROLES as $role) {
                $assignment = $current->get($role);
                $wantsRole = $desired->contains($role);

                if ($wantsRole) {
                    $scope = $role === RetreatRoleAssignment::DEPARTMENT_REVIEWER ? $user->department : null;
                    $changed = ! $assignment || ! $assignment->active || $assignment->scope_department !== $scope;
                    if ($changed) {
                        if ($assignment?->active && $assignment->scope_department !== $scope) {
                            $this->audit($request->user(), $user, 'revoked', $role, $assignment->scope_department);
                        }
                        $user->roleAssignments()->updateOrCreate(
                            ['role' => $role],
                            ['scope_department' => $scope, 'granted_by' => $request->user()->id, 'active' => true],
                        );
                        $this->audit($request->user(), $user, 'granted', $role, $scope);
                    }

                    continue;
                }

                if (! $assignment?->active) {
                    continue;
                }

                RetreatRoleAssignment::query()->where('role', $role)->where('active', true)
                    ->lockForUpdate()->get();
                $this->guardRevocation($request->user(), $user, $assignment);
                $assignment->update(['active' => false, 'granted_by' => $request->user()->id]);
                $this->audit($request->user(), $user, 'revoked', $role, $assignment->scope_department);
            }
        });

        return back()->with('success', "{$user->name}的权限已更新");
    }

    private function guardRevocation(User $actor, User $target, RetreatRoleAssignment $assignment): void
    {
        if ($assignment->role === RetreatRoleAssignment::ADMIN) {
            if ($actor->is($target)) {
                throw ValidationException::withMessages(['roles' => '不能移除自己的管理员权限。']);
            }
            if ($this->activeRoleCount(RetreatRoleAssignment::ADMIN) <= 1) {
                throw ValidationException::withMessages(['roles' => '系统必须至少保留一名管理员。']);
            }
        }

        if ($assignment->role === RetreatRoleAssignment::DEPARTMENT_REVIEWER
            && $this->validDepartmentReviewerCount($assignment->scope_department) <= 1
            && RetreatGroupApprovalNode::query()->where('active', true)->where('stage', 'department')
                ->where('department', $assignment->scope_department)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['roles' => '该单位仍有待审任务，不能撤销最后一名有效审核人。']);
        }

        if ($assignment->role === RetreatRoleAssignment::FINAL_REVIEWER
            && $this->activeRoleCount(RetreatRoleAssignment::FINAL_REVIEWER) <= 1
            && RetreatGroupApprovalNode::query()->where('active', true)->where('stage', 'final')
                ->whereIn('status', ['waiting', 'pending'])->exists()) {
            throw ValidationException::withMessages(['roles' => '仍有待终审任务，不能撤销最后一名总审核人。']);
        }
    }

    private function activeRoleCount(string $role): int
    {
        $query = RetreatRoleAssignment::query()->where('role', $role)->where('active', true);
        if ($role === RetreatRoleAssignment::DEPARTMENT_REVIEWER) {
            $query->whereHas('user', fn ($users) => $users
                ->where('retreat_eligible', true)
                ->whereColumn('users.department', 'retreat_role_assignments.scope_department'));
        } elseif ($role === RetreatRoleAssignment::FINAL_REVIEWER) {
            $query->whereHas('user', fn ($users) => $users->where('retreat_eligible', true));
        }

        return $query->count();
    }

    private function validDepartmentReviewerCount(?string $department): int
    {
        return RetreatRoleAssignment::query()->where('role', RetreatRoleAssignment::DEPARTMENT_REVIEWER)
            ->where('scope_department', $department)->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('department', $department)->where('retreat_eligible', true))->count();
    }

    private function audit(User $actor, User $target, string $action, string $role, ?string $department): void
    {
        RetreatPermissionAudit::create([
            'target_user_id' => $target->id,
            'actor_id' => $actor->id,
            'target_name' => $target->name,
            'target_staff_number' => $target->staff_number,
            'actor_name' => $actor->name,
            'action' => $action,
            'role' => $role,
            'scope_department' => $department,
        ]);
    }
}
