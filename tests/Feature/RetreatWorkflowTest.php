<?php

namespace Tests\Feature;

use App\Models\RetreatGroup;
use App\Models\RetreatGroupApplication;
use App\Models\RetreatRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetreatWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_route_group_and_application_workflow(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'department' => '校工会',
            'email_verified_at' => now(),
        ]);
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'department' => '信息工程学院',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('retreat.routes.store'), $this->routePayload())
            ->assertRedirect(route('retreat.approvals'));

        $route = RetreatRoute::firstOrFail();
        $this->assertSame('pending_department', $route->status);
        $this->assertCount(2, $route->itineraryDays);

        $this->actingAs($admin)
            ->post(route('retreat.approvals.approve', $route), ['comment' => '单位初审通过'])
            ->assertRedirect();
        $this->assertSame('pending_union', $route->fresh()->status);

        $this->actingAs($admin)
            ->post(route('retreat.approvals.approve', $route), ['comment' => '校工会终审通过'])
            ->assertRedirect();
        $this->assertSame('approved', $route->fresh()->status);
        $this->assertCount(2, $route->approvals);

        $this->actingAs($admin)
            ->post(route('retreat.groups.store'), [
                'retreat_route_id' => $route->id,
                'title' => '测试疗休养团',
                'departure_date' => now()->addMonths(2)->toDateString(),
                'return_date' => now()->addMonths(2)->addDays(1)->toDateString(),
                'application_deadline' => now()->addMonth()->toDateString(),
                'min_people' => 2,
                'max_people' => 8,
                'meeting_info' => '学校东门集合',
                'notes' => '请携带身份证',
            ])
            ->assertRedirect();

        $group = RetreatGroup::firstOrFail();
        $leaderMembership = $group->applications()->where('user_id', $admin->id)->firstOrFail();
        $this->assertSame('approved', $leaderMembership->status);
        $this->assertSame(1, $leaderMembership->member_count);

        $this->actingAs($admin)
            ->post(route('retreat.groups.applications.store', $group), [
                'member_count' => 1,
                'family_members' => [],
                'message' => '',
            ])
            ->assertUnprocessable();
        $this->assertSame(1, $group->applications()->where('user_id', $admin->id)->count());

        $this->actingAs($teacher)
            ->post(route('retreat.groups.applications.store', $group), [
                'member_count' => 2,
                'family_members' => [['name' => '家属', 'relationship' => '配偶']],
                'message' => '本人携一名家属参加',
            ])
            ->assertRedirect();

        $application = RetreatGroupApplication::where('user_id', $teacher->id)->firstOrFail();
        $this->assertSame('pending', $application->status);

        $this->actingAs($admin)
            ->post(route('retreat.groups.applications.review', [$group, $application]), [
                'action' => 'approved',
                'comment' => '同意参团',
            ])
            ->assertRedirect();

        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame($admin->id, $application->fresh()->reviewed_by);
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
