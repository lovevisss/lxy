<?php

namespace Tests\Feature;

use App\Models\RetreatGroup;
use App\Models\RetreatRoleAssignment;
use App\Models\RetreatRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RetreatGroupApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_permissions_are_multi_role_audited_and_guarded(): void
    {
        $admin = $this->teacher('校工会', '10001', 'admin');
        $reviewer = $this->teacher('信息工程学院', '10002');
        $outsider = $this->teacher('人文学院', '10003');

        $this->actingAs($outsider)->get(route('retreat.permissions'))->assertForbidden();
        $this->actingAs($admin)->get(route('retreat.permissions'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('retreat/Permissions')->where('stats.admins', 1));

        $this->actingAs($admin)->put(route('retreat.permissions.update', $reviewer), [
            'roles' => [RetreatRoleAssignment::DEPARTMENT_REVIEWER, RetreatRoleAssignment::FINAL_REVIEWER],
        ])->assertRedirect();
        $this->assertTrue($reviewer->isGroupDepartmentReviewer('信息工程学院'));
        $this->assertTrue($reviewer->isGroupFinalReviewer());
        $this->assertDatabaseCount('retreat_permission_audits', 2);

        $reviewer->update(['department' => '人文学院']);
        $this->assertFalse($reviewer->isGroupDepartmentReviewer());
        $this->actingAs($admin)->get(route('retreat.permissions'))->assertInertia(fn (Assert $page) => $page
            ->where('users.data.0.departmentRoleStale', true));

        $this->actingAs($admin)->put(route('retreat.permissions.update', $reviewer), [
            'roles' => [RetreatRoleAssignment::DEPARTMENT_REVIEWER, RetreatRoleAssignment::FINAL_REVIEWER],
        ])->assertRedirect();
        $this->assertTrue($reviewer->isGroupDepartmentReviewer('人文学院'));
        $this->assertDatabaseCount('retreat_permission_audits', 4);

        $this->actingAs($admin)->put(route('retreat.permissions.update', $admin), ['roles' => []])
            ->assertSessionHasErrors('roles');
        $this->assertTrue($admin->isRetreatAdmin());
    }

    public function test_three_departments_concur_before_final_review_and_admin_cannot_approve(): void
    {
        [$admin, $leader, $a, $b, $c, $final, $group] = $this->team();

        $this->actingAs($leader)->post(route('retreat.groups.approval.submit', $group))->assertRedirect();
        $this->assertSame('pending_departments', $group->fresh()->approval_status);
        $this->actingAs($a)->get(route('retreat.groups.show', $group))->assertInertia(fn (Assert $page) => $page
            ->where('approvalFlow', null)
            ->has('applications', 0)
            ->where('leaderApplication', null));
        $this->assertSame(3, $group->approvalNodes()->where('stage', 'department')->count());
        $this->assertSame(1, $group->approvalNodes()->where('stage', 'final')->count());
        $this->assertSame(4, collect($group->approvalNodes()->where('stage', 'final')->firstOrFail()->member_snapshot)->count());

        $this->actingAs($admin)->put(route('retreat.permissions.update', $a), ['roles' => []])
            ->assertSessionHasErrors('roles');
        $this->actingAs($admin)->put(route('retreat.permissions.update', $final), ['roles' => []])
            ->assertSessionHasErrors('roles');
        $alternateA = $this->teacher('甲学院', '10007');
        $this->grant($alternateA, RetreatRoleAssignment::DEPARTMENT_REVIEWER);

        $finalNode = $group->approvalNodes()->where('stage', 'final')->firstOrFail();
        $this->actingAs($final)->post(route('retreat.group-approvals.review', $finalNode), ['action' => 'approved'])
            ->assertSessionHasErrors('approval');
        $nodeA = $group->approvalNodes()->where('department', '甲学院')->firstOrFail();
        $this->actingAs($admin)->post(route('retreat.group-approvals.review', $nodeA), ['action' => 'approved'])
            ->assertForbidden();
        $this->actingAs($b)->post(route('retreat.group-approvals.review', $nodeA), ['action' => 'approved'])
            ->assertForbidden();

        foreach (['甲学院' => $alternateA, '乙学院' => $b, '丙学院' => $c] as $department => $reviewer) {
            $node = $group->approvalNodes()->where('department', $department)->firstOrFail();
            $this->actingAs($reviewer)->post(route('retreat.group-approvals.review', $node), ['action' => 'approved'])
                ->assertRedirect();
        }
        $this->assertSame('pending_final', $group->fresh()->approval_status);
        $this->actingAs($a)->post(route('retreat.group-approvals.review', $nodeA), ['action' => 'approved'])
            ->assertSessionHasErrors('approval');
        $this->actingAs($final)->post(route('retreat.group-approvals.review', $finalNode), ['action' => 'approved'])
            ->assertRedirect();
        $this->assertSame('formed', $group->fresh()->status);
        $this->assertSame('approved', $group->fresh()->approval_status);
        $this->assertDatabaseHas('retreat_sms_notifications', ['retreat_group_id' => $group->id, 'event' => 'formed_confirmation']);
    }

    public function test_member_change_only_resets_affected_department_and_family_does_not_reset(): void
    {
        [$admin, $leader, $a, $b, $c, $final, $group] = $this->team();
        $this->actingAs($leader)->post(route('retreat.groups.approval.submit', $group))->assertRedirect();
        foreach (['甲学院' => $a, '乙学院' => $b, '丙学院' => $c] as $department => $reviewer) {
            $node = $group->approvalNodes()->where('department', $department)->firstOrFail();
            $this->actingAs($reviewer)->post(route('retreat.group-approvals.review', $node), ['action' => 'approved'])->assertRedirect();
        }

        $leaderApplication = $group->applications()->where('user_id', $leader->id)->firstOrFail();
        $this->actingAs($leader)->put(route('retreat.groups.leader-family.update', $group), [
            'family_members' => [['name' => '家属', 'relationship' => '配偶']],
        ])->assertRedirect();
        $this->assertSame('pending_final', $group->fresh()->approval_status);
        $this->assertSame(3, $group->approvalNodes()->where('stage', 'department')->where('status', 'approved')->count());
        $this->assertSame(2, $leaderApplication->fresh()->member_count);

        $added = $this->teacher('乙学院', '10009');
        $this->actingAs($leader)->post(route('retreat.groups.members.store', $group), [
            'user_id' => $added->id,
            'contact_mobile' => '13800000009',
            'family_members' => [],
        ])->assertRedirect();
        $this->assertSame('pending_departments', $group->fresh()->approval_status);
        $this->assertSame('pending', $group->approvalNodes()->where('department', '乙学院')->firstOrFail()->status);
        $this->assertSame('approved', $group->approvalNodes()->where('department', '甲学院')->firstOrFail()->status);
        $this->assertSame('approved', $group->approvalNodes()->where('department', '丙学院')->firstOrFail()->status);

        $noReviewer = $this->teacher('丁学院', '10010');
        $this->actingAs($leader)->post(route('retreat.groups.members.store', $group), [
            'user_id' => $noReviewer->id,
            'contact_mobile' => '13800000010',
            'family_members' => [],
        ])->assertSessionHasErrors('user_id');

        $addedApplication = $group->applications()->where('user_id', $added->id)->firstOrFail();
        $this->actingAs($leader)->delete(route('retreat.groups.members.destroy', [$group, $addedApplication]))->assertRedirect();
        $this->assertSame('pending', $group->approvalNodes()->where('department', '乙学院')->firstOrFail()->status);
        $this->assertSame('approved', $group->approvalNodes()->where('department', '甲学院')->firstOrFail()->status);

        $nodeB = $group->approvalNodes()->where('department', '乙学院')->firstOrFail();
        $this->actingAs($b)->post(route('retreat.group-approvals.review', $nodeB), [
            'action' => 'rejected', 'comment' => '请核对名单',
        ])->assertRedirect();
        $this->assertSame('returned', $group->fresh()->approval_status);
        $this->actingAs($leader)->post(route('retreat.groups.approval.submit', $group))->assertRedirect();
        $this->assertSame('approved', $group->approvalNodes()->where('department', '甲学院')->firstOrFail()->status);
        $this->assertSame('pending', $group->approvalNodes()->where('department', '乙学院')->firstOrFail()->status);
        $this->assertSame('approved', $group->approvalNodes()->where('department', '丙学院')->firstOrFail()->status);
    }

    private function teacher(string $department, string $number, string $role = 'teacher'): User
    {
        return User::factory()->create([
            'name' => "教师{$number}",
            'role' => $role,
            'department' => $department,
            'staff_number' => $number,
            'identity_source' => 'cas',
            'retreat_eligible' => true,
            'mobile' => '13800000001',
        ]);
    }

    private function grant(User $user, string $role): void
    {
        $user->roleAssignments()->create([
            'role' => $role,
            'scope_department' => $role === RetreatRoleAssignment::DEPARTMENT_REVIEWER ? $user->department : null,
            'active' => true,
        ]);
    }

    private function team(): array
    {
        $admin = $this->teacher('校工会', '10001', 'admin');
        $leader = $this->teacher('甲学院', '10002');
        $a = $this->teacher('甲学院', '10003');
        $b = $this->teacher('乙学院', '10004');
        $c = $this->teacher('丙学院', '10005');
        $final = $this->teacher('校工会', '10006');
        foreach ([$a, $b, $c] as $reviewer) {
            $this->grant($reviewer, RetreatRoleAssignment::DEPARTMENT_REVIEWER);
        }
        $this->grant($final, RetreatRoleAssignment::FINAL_REVIEWER);

        $route = RetreatRoute::create([
            'creator_id' => $leader->id,
            'title' => '测试线路', 'location' => '杭州', 'summary' => '测试', 'days' => 2,
            'min_people' => 2, 'max_people' => 10, 'departure_city' => '杭州', 'return_city' => '杭州',
            'hotel_standard' => '标准', 'meal_standard' => '标准', 'local_transport' => '大巴',
            'ticket_standard' => '门票', 'guide_service' => '导游', 'insurance' => '保险',
            'status' => 'approved',
        ]);
        $group = RetreatGroup::create([
            'retreat_route_id' => $route->id,
            'leader_id' => $leader->id,
            'title' => '跨院测试团',
            'departure_date' => now()->addMonth(),
            'return_date' => now()->addMonth()->addDays(2),
            'application_deadline' => now()->addWeeks(2),
            'min_people' => 2, 'max_people' => 10,
            'status' => 'open', 'approval_mode' => 'manual',
        ]);
        foreach ([$leader, $a, $b, $c] as $user) {
            $group->applications()->create([
                'user_id' => $user->id,
                'member_count' => $user->id === $leader->id ? 2 : 1,
                'family_members' => $user->id === $leader->id ? [['name' => '家属', 'relationship' => '配偶']] : [],
                'contact_mobile' => $user->mobile,
                'status' => 'approved',
            ]);
        }

        return [$admin, $leader, $a, $b, $c, $final, $group];
    }
}
