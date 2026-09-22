<?php

namespace Tests\Feature;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatGroupApprovalNode;
use App\Models\RetreatGroupReview;
use App\Models\RetreatRoleAssignment;
use App\Models\RetreatRoute;
use App\Models\RetreatSmsNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RetreatWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_route_group_and_application_workflow(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'department' => '校工会',
            'retreat_eligible' => true,
            'email_verified_at' => now(),
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'department' => '信息工程学院',
            'retreat_eligible' => true,
            'email_verified_at' => now(),
        ]);
        $departmentApprover = User::factory()->create(['role' => 'department_approver', 'department' => '信息工程学院']);
        $unionApprover = User::factory()->create(['role' => 'union_approver', 'department' => '校工会', 'retreat_eligible' => true]);

        $this->actingAs($admin)
            ->post(route('retreat.routes.store'), $this->routePayload())
            ->assertRedirect(route('retreat.routes.show', 1));

        $route = RetreatRoute::firstOrFail();
        $this->assertSame('pending_department', $route->status);
        $this->assertCount(2, $route->itineraryDays);
        $this->assertSame(['往返高铁票', '个人消费'], $route->self_funded_items);

        $this->actingAs($departmentApprover)
            ->post(route('retreat.approvals.approve', $route), ['comment' => '单位初审通过'])
            ->assertRedirect();
        $this->assertSame('pending_union', $route->fresh()->status);

        $this->actingAs($unionApprover)
            ->post(route('retreat.approvals.approve', $route), ['comment' => '校工会终审通过'])
            ->assertRedirect();
        $this->assertSame('approved', $route->fresh()->status);
        $this->assertCount(2, $route->approvals);

        $this->actingAs($teacher)
            ->get(route('retreat.routes.import'))
            ->assertForbidden();
        $templateResponse = $this->actingAs($admin)
            ->get(route('retreat.routes.import.template'))
            ->assertOk();
        $templateFile = UploadedFile::fake()->createWithContent(
            '工会线路模板.csv',
            $templateResponse->streamedContent(),
        );
        $this->actingAs($admin)
            ->post(route('retreat.routes.import.store'), ['file' => $templateFile])
            ->assertRedirect();
        $importedRoute = RetreatRoute::where('title', '湖山疗愈·杭州两日')->firstOrFail();
        $this->assertSame('approved', $importedRoute->status);
        $this->assertNotNull($importedRoute->retreat_route_import_id);
        $this->assertCount(2, $importedRoute->itineraryDays);
        $this->assertDatabaseHas('retreat_route_imports', [
            'status' => 'success',
            'total_routes' => 1,
            'imported_routes' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('retreat.groups.store'), [
                'retreat_route_id' => $route->id,
                'title' => '测试疗休养团',
                'departure_date' => now()->addMonths(2)->toDateString(),
                'return_date' => now()->addMonths(2)->addDays(1)->toDateString(),
                'application_deadline' => now()->addMonth()->toDateString(),
                'min_people' => 2,
                'max_people' => 8,
                'approval_mode' => 'manual',
                'meeting_info' => '学校东门集合',
                'notes' => '请携带身份证',
                'contact_mobile' => '13800000001',
            ])
            ->assertRedirect();

        $group = RetreatGroup::firstOrFail();
        $leaderMembership = $group->applications()->where('user_id', $admin->id)->firstOrFail();
        $this->assertSame('approved', $leaderMembership->status);
        $this->assertSame(1, $leaderMembership->member_count);

        $this->actingAs($admin)
            ->put(route('retreat.groups.leader-family.update', $group), [
                'family_members' => [['name' => '团长家属', 'relationship' => '配偶']],
            ])
            ->assertRedirect();
        $leaderMembership->refresh();
        $this->assertSame(2, $leaderMembership->member_count);
        $this->assertSame('团长家属', $leaderMembership->family_members[0]['name']);

        $directMember = User::factory()->create([
            'role' => 'teacher',
            'department' => '外国语学院',
            'retreat_eligible' => true,
            'email_verified_at' => now(),
            'mobile' => '13800000004',
        ]);
        $this->actingAs($admin)
            ->post(route('retreat.groups.members.store', $group), [
                'user_id' => $directMember->id,
                'family_members' => [['name' => '同行子女', 'relationship' => '子女']],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $directMembership = $group->applications()->where('user_id', $directMember->id)->firstOrFail();
        $this->assertSame('approved', $directMembership->status);
        $this->assertSame(2, $directMembership->member_count);
        $this->assertSame('13800000004', $directMembership->contact_mobile);
        $this->assertSame($admin->id, $directMembership->reviewed_by);
        $this->assertSame('团长手动添加成员', $directMembership->review_comment);

        $this->actingAs($admin)
            ->get(route('retreat.groups.show', $group))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('retreat/GroupDetail')
                ->where('leaderApplication.memberCount', 2)
                ->where('leaderApplication.familyMembers.0.name', '团长家属')
                ->has('availableMembers', 3)
                ->has('applications', 1)
                ->where('applications.0.name', $directMember->name)
                ->where('applications.0.manuallyAdded', true));

        $this->actingAs($teacher)
            ->post(route('retreat.groups.members.store', $group), [
                'user_id' => $teacher->id,
                'contact_mobile' => '13800000005',
                'family_members' => [],
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('retreat.groups.applications.store', $group), [
                'member_count' => 1,
                'family_members' => [],
                'message' => '',
            ])
            ->assertUnprocessable();
        $this->assertSame(1, $group->applications()->where('user_id', $admin->id)->count());

        Storage::fake('local');
        $this->actingAs($admin)
            ->post(route('retreat.groups.update', $group), [
                '_method' => 'put',
                'title' => '测试疗休养团（已更新）',
                'departure_date' => now()->addMonths(2)->toDateString(),
                'return_date' => now()->addMonths(2)->addDays(1)->toDateString(),
                'application_deadline' => now()->addMonth()->toDateString(),
                'min_people' => 2,
                'max_people' => 8,
                'approval_mode' => 'manual',
                'meeting_info' => '学校东门 07:30 集合',
                'notes' => '请携带身份证',
                'attachment' => UploadedFile::fake()->create('活动方案.pdf', 128, 'application/pdf'),
                'wechat_qr_code' => UploadedFile::fake()->image('微信群二维码.png', 600, 600),
            ])
            ->assertRedirect(route('retreat.groups.show', $group))
            ->assertSessionHasNoErrors();

        $group->refresh();
        $this->assertSame('活动方案.pdf', $group->attachment_name);
        $this->assertSame('微信群二维码.png', $group->wechat_qr_code_name);
        Storage::disk('local')->assertExists($group->attachment_path);
        Storage::disk('local')->assertExists($group->wechat_qr_code_path);
        $this->actingAs($admin)
            ->get(route('retreat.groups.wechat-qr-code', $group))
            ->assertOk();
        $this->actingAs($teacher)
            ->get(route('retreat.groups.wechat-qr-code', $group))
            ->assertForbidden();
        $this->actingAs($teacher)
            ->get(route('retreat.groups.attachment', $group))
            ->assertOk();

        $this->actingAs($teacher)
            ->post(route('retreat.groups.applications.store', $group), [
                'member_count' => 2,
                'family_members' => [['name' => '家属', 'relationship' => '配偶']],
                'contact_mobile' => '13800000002',
                'message' => '本人携一名家属参加',
            ])
            ->assertRedirect();

        $application = RetreatGroupApplication::where('user_id', $teacher->id)->firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertSame('家属', $application->family_members[0]['name']);

        $this->actingAs($admin)
            ->post(route('retreat.groups.applications.review', [$group, $application]), [
                'action' => 'approved',
                'comment' => '同意参团',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame($admin->id, $application->fresh()->reviewed_by);
        $this->actingAs($teacher)
            ->get(route('retreat.groups.wechat-qr-code', $group))
            ->assertOk();
        $this->actingAs($teacher)
            ->get(route('retreat.groups.show', $group))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('groupRecord.wechatQrCodeName', '微信群二维码.png')
                ->where('groupRecord.wechatQrCodeUrl', route('retreat.groups.wechat-qr-code', $group)));

        $admin->roleAssignments()->create(['role' => RetreatRoleAssignment::DEPARTMENT_REVIEWER, 'scope_department' => '校工会', 'active' => true]);
        $teacher->roleAssignments()->create(['role' => RetreatRoleAssignment::DEPARTMENT_REVIEWER, 'scope_department' => '信息工程学院', 'active' => true]);
        $directMember->roleAssignments()->create(['role' => RetreatRoleAssignment::DEPARTMENT_REVIEWER, 'scope_department' => '外国语学院', 'active' => true]);
        $unionApprover->roleAssignments()->create(['role' => RetreatRoleAssignment::FINAL_REVIEWER, 'active' => true]);
        $this->actingAs($admin)->post(route('retreat.groups.approval.submit', $group))->assertRedirect();
        foreach (RetreatGroupApprovalNode::where('retreat_group_id', $group->id)->where('stage', 'department')->get() as $node) {
            $reviewer = match ($node->department) {
                '校工会' => $admin,
                '信息工程学院' => $teacher,
                default => $directMember,
            };
            $this->actingAs($reviewer)
                ->post(route('retreat.group-approvals.review', $node), ['action' => 'approved'])
                ->assertRedirect();
        }
        $finalNode = RetreatGroupApprovalNode::where('retreat_group_id', $group->id)->where('stage', 'final')->firstOrFail();
        $this->actingAs($unionApprover)
            ->post(route('retreat.group-approvals.review', $finalNode), ['action' => 'approved'])
            ->assertRedirect();
        $this->assertSame('formed', $group->fresh()->status);
        $this->assertSame('pending', $application->fresh()->final_confirmation_status);
        $this->assertSame('pending', $directMembership->fresh()->final_confirmation_status);
        $this->assertDatabaseHas('retreat_sms_notifications', [
            'retreat_group_application_id' => $directMembership->id,
            'event' => 'formed_confirmation',
            'mobile' => '13800000004',
        ]);
        $sms = RetreatSmsNotification::where('retreat_group_application_id', $application->id)->firstOrFail();
        $this->assertSame('formed_confirmation', $sms->event);
        $this->assertSame('pending_provider', $sms->status);
        $this->assertSame('13800000002', $sms->mobile);

        $this->actingAs($teacher)
            ->post(route('retreat.groups.final-confirmation', $group), ['action' => 'confirmed'])
            ->assertRedirect();
        $this->assertSame('confirmed', $application->fresh()->final_confirmation_status);

        $reviewPayload = [
            'retreat_group_id' => $group->id,
            'route_score' => 5,
            'meal_score' => 4,
            'attraction_score' => 5,
            'accommodation_score' => 4,
            'service_score' => 5,
            'comment' => '路线节奏舒适，景点讲解很充实。',
        ];

        $this->actingAs($teacher)
            ->post(route('retreat.routes.reviews.store', $route), $reviewPayload)
            ->assertUnprocessable();

        $group->update([
            'departure_date' => now()->subDays(8),
            'return_date' => now()->subDays(4),
            'application_deadline' => now()->subDays(12),
            'status' => 'formed',
        ]);

        $this->actingAs($teacher)
            ->post(route('retreat.routes.reviews.store', $route), $reviewPayload)
            ->assertRedirect();

        $review = RetreatGroupReview::firstOrFail();
        $this->assertSame($route->id, $review->retreat_route_id);
        $this->assertSame(5, $review->route_score);
        $this->assertSame(4.6, $review->overallScore());

        $this->actingAs($teacher)
            ->post(route('retreat.routes.reviews.store', $route), [
                ...$reviewPayload,
                'meal_score' => 5,
            ])
            ->assertRedirect();
        $this->assertSame(1, RetreatGroupReview::count());
        $this->assertSame(5, $review->fresh()->meal_score);

        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($outsider)
            ->post(route('retreat.routes.reviews.store', $route), $reviewPayload)
            ->assertForbidden();

        $failedGroup = RetreatGroup::create([
            'retreat_route_id' => $route->id,
            'leader_id' => $admin->id,
            'title' => '报名不足测试团',
            'departure_date' => now()->addMonth(),
            'return_date' => now()->addMonth()->addDays(1),
            'application_deadline' => now()->subDay(),
            'min_people' => 5,
            'max_people' => 8,
            'status' => 'open',
        ]);
        $failedGroup->applications()->create([
            'user_id' => $teacher->id,
            'member_count' => 1,
            'family_members' => [],
            'contact_mobile' => '13800000002',
            'status' => 'approved',
        ]);
        $this->artisan('retreat:process-deadlines')->assertSuccessful();
        $this->assertSame('failed', $failedGroup->fresh()->status);
        $this->assertDatabaseHas('retreat_sms_notifications', [
            'retreat_group_id' => $failedGroup->id,
            'event' => 'formation_failed',
            'mobile' => '13800000002',
        ]);

        $cancelledGroup = RetreatGroup::create([
            'retreat_route_id' => $route->id,
            'leader_id' => $admin->id,
            'title' => '团长取消测试团',
            'departure_date' => now()->addMonths(2),
            'return_date' => now()->addMonths(2)->addDays(1),
            'application_deadline' => now()->addMonth(),
            'min_people' => 2,
            'max_people' => 8,
            'status' => 'open',
        ]);
        $cancelledGroup->applications()->create([
            'user_id' => $teacher->id,
            'member_count' => 1,
            'family_members' => [],
            'contact_mobile' => '13800000002',
            'status' => 'approved',
        ]);
        $this->actingAs($admin)
            ->post(route('retreat.groups.status', $cancelledGroup), [
                'action' => 'cancelled',
                'reason' => '学校临时调整本期安排',
            ])
            ->assertRedirect();
        $this->assertSame('cancelled', $cancelledGroup->fresh()->status);
        $this->assertDatabaseHas('retreat_sms_notifications', [
            'retreat_group_id' => $cancelledGroup->id,
            'event' => 'group_cancelled',
            'mobile' => '13800000002',
        ]);

        $automaticGroup = RetreatGroup::create([
            'retreat_route_id' => $route->id,
            'leader_id' => $admin->id,
            'title' => '自动参团测试团',
            'departure_date' => now()->addMonths(3),
            'return_date' => now()->addMonths(3)->addDays(1),
            'application_deadline' => now()->addMonths(2),
            'min_people' => 2,
            'max_people' => 8,
            'approval_mode' => 'automatic',
            'status' => 'open',
        ]);
        $automaticApplicant = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($automaticApplicant)
            ->post(route('retreat.groups.applications.store', $automaticGroup), [
                'member_count' => 1,
                'family_members' => [],
                'contact_mobile' => '13800000003',
                'message' => '自动参团',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('retreat_group_applications', [
            'retreat_group_id' => $automaticGroup->id,
            'user_id' => $automaticApplicant->id,
            'status' => 'approved',
            'review_comment' => '按组团设置自动通过',
        ]);
    }

    /** @return array<string, mixed> */
    private function routePayload(): array
    {
        return [
            'title' => '湖山疗愈 · 测试两日线路',
            'region' => '华东',
            'location' => '浙江 · 杭州',
            'summary' => '用于验证从线路申报、两级审批到组团报名审核的完整流程。',
            'min_people' => 2,
            'max_people' => 10,
            'departure_city' => '杭州',
            'return_city' => '杭州',
            'inbound_transport' => '统一大巴',
            'outbound_transport' => '统一大巴',
            'highlights' => ['湖畔漫行', '人文走读'],
            'experiences' => '轻徒步与文化体验',
            'hotel_standard' => '舒适型酒店双人标准间',
            'meal_standard' => '一早三正餐',
            'local_transport' => '全程空调旅游车',
            'ticket_standard' => '包含首道门票',
            'guide_service' => '专业中文导游',
            'insurance' => '旅行意外保险',
            'value_added' => ['饮用水'],
            'self_funded_items' => ['往返高铁票', '个人消费'],
            'notices' => ['携带有效身份证件'],
            'cover_generated' => true,
            'days' => [
                [
                    'title' => '集合出发 · 湖畔漫行',
                    'location' => '杭州',
                    'transport' => '统一大巴',
                    'morning' => '学校集合出发',
                    'afternoon' => '湖畔慢行',
                    'evening' => '团队交流',
                    'meals' => '中餐、晚餐',
                    'stay' => '杭州酒店',
                ],
                [
                    'title' => '人文走读 · 从容返程',
                    'location' => '杭州',
                    'transport' => '统一大巴',
                    'morning' => '文化场馆走读',
                    'afternoon' => '返校',
                    'meals' => '早餐、中餐',
                    'stay' => '—',
                ],
            ],
        ];
    }
}
