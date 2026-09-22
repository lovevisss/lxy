<?php

namespace App\Services;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApprovalEvent;
use App\Models\RetreatGroupApprovalNode;
use App\Models\RetreatRoleAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetreatGroupApprovalService
{
    public function __construct(private readonly RetreatGroupLifecycleService $lifecycle) {}

    public function submit(RetreatGroup $group, User $actor): void
    {
        if ($group->status !== 'open' || ! in_array($group->approval_status, ['not_submitted', 'returned'], true)) {
            throw ValidationException::withMessages(['approval' => '当前团队状态无法提交成团审批。']);
        }

        if ($group->applications()->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['approval' => '请先处理全部待审核的参团申请。']);
        }

        $joined = (int) $group->applications()->where('status', 'approved')->sum('member_count');
        if ($joined < $group->min_people) {
            throw ValidationException::withMessages([
                'approval' => "当前已确认 {$joined} 人，未达到最低成团人数 {$group->min_people} 人。",
            ]);
        }

        $members = $this->approvedMembers($group);
        $departments = $members->pluck('user.department')->filter()->unique()->values();
        if ($members->contains(fn ($application) => ! filled($application->user->department))) {
            throw ValidationException::withMessages(['approval' => '正式团员中存在未设置所属单位的账号，请先完善人员信息。']);
        }

        foreach ($departments as $department) {
            if (! $this->hasActiveDepartmentReviewer($department)) {
                throw ValidationException::withMessages([
                    'approval' => "“{$department}”尚未配置有效的分院审核人，请联系管理员。",
                ]);
            }
        }

        if (! $this->hasActiveFinalReviewer()) {
            throw ValidationException::withMessages(['approval' => '尚未配置总审核人，请联系管理员。']);
        }

        DB::transaction(function () use ($group, $actor, $members): void {
            $locked = RetreatGroup::query()->lockForUpdate()->findOrFail($group->id);
            if ($locked->status !== 'open' || ! in_array($locked->approval_status, ['not_submitted', 'returned'], true)) {
                throw ValidationException::withMessages(['approval' => '当前团队状态无法提交成团审批。']);
            }
            $this->syncNodes($locked, $members, resetRejected: true);
            $locked->update([
                'approval_status' => $this->allDepartmentsApproved($locked) ? 'pending_final' : 'pending_departments',
                'approval_submitted_at' => now(),
                'approval_returned_reason' => null,
            ]);
            $this->syncFinalNodeStatus($locked);
            $this->record($locked, null, $actor, 'submitted', null, null, '团长提交成团审批');
        });
    }

    public function refreshRoster(RetreatGroup $group, User $actor, string $reason): void
    {
        if ($group->approval_status === 'not_submitted' || $group->approval_status === 'approved') {
            return;
        }

        DB::transaction(function () use ($group, $actor, $reason): void {
            $locked = RetreatGroup::query()->lockForUpdate()->findOrFail($group->id);
            $before = $locked->approvalNodes()->where('active', true)->where('stage', 'department')
                ->pluck('roster_hash', 'department');
            $this->syncNodes($locked, $this->approvedMembers($locked), resetRejected: false);
            $after = $locked->approvalNodes()->where('active', true)->where('stage', 'department')
                ->pluck('roster_hash', 'department');
            $changed = $before->keys()->merge($after->keys())->unique()
                ->filter(fn (string $department) => $before->get($department) !== $after->get($department))
                ->values()->all();

            $joined = (int) $locked->applications()->where('status', 'approved')->sum('member_count');
            if ($joined < $locked->min_people) {
                $locked->update([
                    'approval_status' => 'returned',
                    'approval_returned_reason' => "当前已确认 {$joined} 人，未达到最低成团人数 {$locked->min_people} 人。",
                ]);
                $locked->approvalNodes()->where('stage', 'final')->update(['status' => 'waiting']);
            }

            if ($locked->approval_status !== 'returned') {
                $locked->update([
                    'approval_status' => $this->allDepartmentsApproved($locked) ? 'pending_final' : 'pending_departments',
                ]);
                $this->syncFinalNodeStatus($locked);
            }

            $this->record($locked, null, $actor, 'roster_changed', null, null, $reason, [
                'affected_departments' => $changed,
            ]);
        });
    }

    public function invalidateFinalReview(RetreatGroup $group, User $actor, string $reason): void
    {
        if (! in_array($group->approval_status, ['pending_departments', 'pending_final', 'returned'], true)) {
            return;
        }

        DB::transaction(function () use ($group, $actor, $reason): void {
            $locked = RetreatGroup::query()->lockForUpdate()->findOrFail($group->id);
            $node = $locked->approvalNodes()->where('scope_key', '__final__')->first();
            if ($node) {
                $node->update([
                    'status' => $this->allDepartmentsApproved($locked) ? 'pending' : 'waiting',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'comment' => null,
                ]);
            }
            if ($locked->approval_status !== 'returned') {
                $locked->update(['approval_status' => $this->allDepartmentsApproved($locked) ? 'pending_final' : 'pending_departments']);
            }
            $this->record($locked, $node, $actor, 'final_invalidated', 'final', null, $reason);
        });
    }

    public function refreshFamilySnapshot(RetreatGroup $group, User $actor): void
    {
        if ($group->approval_status === 'not_submitted' || $group->approval_status === 'approved') {
            return;
        }

        DB::transaction(function () use ($group, $actor): void {
            $locked = RetreatGroup::query()->lockForUpdate()->findOrFail($group->id);
            $members = $this->approvedMembers($locked);
            $this->syncNodes($locked, $members, resetRejected: false);
            $this->record($locked, null, $actor, 'family_changed', null, null, '随行家属信息更新，不触发重新会签');
        });
    }

    public function review(RetreatGroupApprovalNode $node, User $actor, string $action, ?string $comment): int
    {
        if ($action === 'rejected' && trim((string) $comment) === '') {
            throw ValidationException::withMessages(['comment' => '退回时必须填写审批意见。']);
        }

        return DB::transaction(function () use ($node, $actor, $action, $comment): int {
            $group = RetreatGroup::query()->lockForUpdate()->findOrFail($node->retreat_group_id);
            $lockedNode = RetreatGroupApprovalNode::query()->lockForUpdate()->findOrFail($node->id);

            if (! $lockedNode->active || $lockedNode->status !== 'pending') {
                throw ValidationException::withMessages(['approval' => '该审批任务已处理或已失效。']);
            }

            if ($lockedNode->stage === 'department') {
                abort_unless($actor->isGroupDepartmentReviewer($lockedNode->department), 403);
            } else {
                abort_unless($actor->isGroupFinalReviewer(), 403);
                if (! $this->allDepartmentsApproved($group)) {
                    throw ValidationException::withMessages(['approval' => '仍有分院尚未完成会签。']);
                }
            }

            if ($group->status !== 'open' || ! in_array($group->approval_status, ['pending_departments', 'pending_final'], true)) {
                throw ValidationException::withMessages(['approval' => '当前团队已不在审批流程中。']);
            }

            $lockedNode->update([
                'status' => $action,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'comment' => $comment,
            ]);
            $this->record($group, $lockedNode, $actor, $action, $lockedNode->stage, $lockedNode->department, $comment, [
                'members' => $lockedNode->member_snapshot,
                'roster_hash' => $lockedNode->roster_hash,
            ]);

            if ($action === 'rejected') {
                $group->update([
                    'approval_status' => 'returned',
                    'approval_returned_reason' => $comment,
                ]);

                return 0;
            }

            if ($lockedNode->stage === 'department') {
                if ($this->allDepartmentsApproved($group)) {
                    $group->update(['approval_status' => 'pending_final']);
                    $this->syncFinalNodeStatus($group);
                }

                return 0;
            }

            $group->update([
                'approval_status' => 'approved',
                'approval_returned_reason' => null,
            ]);

            return $this->lifecycle->form($group);
        });
    }

    public function hasActiveDepartmentReviewer(?string $department): bool
    {
        if (! $department) {
            return false;
        }

        return RetreatRoleAssignment::query()
            ->where('role', RetreatRoleAssignment::DEPARTMENT_REVIEWER)
            ->where('scope_department', $department)
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('department', $department)->where('retreat_eligible', true))
            ->exists();
    }

    public function hasActiveFinalReviewer(): bool
    {
        return RetreatRoleAssignment::query()
            ->where('role', RetreatRoleAssignment::FINAL_REVIEWER)
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('retreat_eligible', true))
            ->exists();
    }

    /** @return Collection<int, mixed> */
    private function approvedMembers(RetreatGroup $group): Collection
    {
        return $group->applications()
            ->with('user')
            ->where('status', 'approved')
            ->get();
    }

    private function syncNodes(RetreatGroup $group, Collection $members, bool $resetRejected): void
    {
        $departments = $members->groupBy(fn ($application) => $application->user->department);
        $activeKeys = [];

        foreach ($departments as $department => $departmentMembers) {
            if (! $department) {
                continue;
            }

            $snapshot = $departmentMembers->sortBy('user_id')->values()->map(fn ($application) => [
                'application_id' => $application->id,
                'user_id' => $application->user_id,
                'name' => $application->user->name,
                'staff_number' => $application->user->staff_number,
                'department' => $application->user->department,
                'family_members' => $application->family_members ?? [],
                'member_count' => $application->member_count,
            ])->all();
            $hash = hash('sha256', json_encode(array_column($snapshot, 'user_id'), JSON_THROW_ON_ERROR));
            $scopeKey = 'department:'.sha1($department);
            $activeKeys[] = $scopeKey;
            $node = $group->approvalNodes()->where('scope_key', $scopeKey)->first();

            if (! $node) {
                $group->approvalNodes()->create([
                    'stage' => 'department',
                    'scope_key' => $scopeKey,
                    'department' => $department,
                    'roster_hash' => $hash,
                    'member_snapshot' => $snapshot,
                    'status' => 'pending',
                    'active' => true,
                ]);
            } else {
                $changed = $node->roster_hash !== $hash;
                $node->update([
                    'department' => $department,
                    'roster_hash' => $hash,
                    'member_snapshot' => $snapshot,
                    'active' => true,
                    ...($changed || ($resetRejected && $node->status === 'rejected') ? [
                        'status' => 'pending',
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'comment' => null,
                    ] : []),
                ]);
            }
        }

        $obsolete = $group->approvalNodes()->where('stage', 'department');
        if ($activeKeys) {
            $obsolete->whereNotIn('scope_key', $activeKeys);
        }
        $obsolete->update(['active' => false, 'status' => 'superseded']);

        $allSnapshot = $members->sortBy('user_id')->values()->map(fn ($application) => [
            'application_id' => $application->id,
            'user_id' => $application->user_id,
            'name' => $application->user->name,
            'staff_number' => $application->user->staff_number,
            'department' => $application->user->department,
            'family_members' => $application->family_members ?? [],
            'member_count' => $application->member_count,
        ])->all();
        $finalHash = hash('sha256', json_encode(array_column($allSnapshot, 'user_id'), JSON_THROW_ON_ERROR));
        $final = $group->approvalNodes()->where('scope_key', '__final__')->first();
        $finalChanged = $final && $final->roster_hash !== $finalHash;
        $values = [
            'stage' => 'final',
            'department' => null,
            'roster_hash' => $finalHash,
            'member_snapshot' => $allSnapshot,
            'active' => true,
        ];
        if (! $final || $finalChanged || ($resetRejected && $final->status === 'rejected')) {
            $values += [
                'status' => 'waiting',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'comment' => null,
            ];
        }
        $group->approvalNodes()->updateOrCreate(['scope_key' => '__final__'], $values);
    }

    private function allDepartmentsApproved(RetreatGroup $group): bool
    {
        return ! $group->approvalNodes()->where('active', true)->where('stage', 'department')
            ->where('status', '!=', 'approved')->exists();
    }

    private function syncFinalNodeStatus(RetreatGroup $group): void
    {
        $final = $group->approvalNodes()->where('scope_key', '__final__')->first();
        if (! $final || $final->status === 'approved') {
            return;
        }

        $final->update(['status' => $this->allDepartmentsApproved($group) ? 'pending' : 'waiting']);
    }

    private function record(
        RetreatGroup $group,
        ?RetreatGroupApprovalNode $node,
        ?User $actor,
        string $event,
        ?string $stage,
        ?string $department,
        ?string $comment,
        array $metadata = [],
    ): void {
        RetreatGroupApprovalEvent::create([
            'retreat_group_id' => $group->id,
            'approval_node_id' => $node?->id,
            'actor_id' => $actor?->id,
            'event' => $event,
            'stage' => $stage,
            'department' => $department,
            'comment' => $comment,
            'metadata' => $metadata ?: null,
        ]);
    }
}
