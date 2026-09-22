<?php

namespace Tests\Feature;

use App\Models\RetreatGroup;
use App\Models\RetreatRoute;
use App\Models\User;
use Database\Seeders\RetreatDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetreatPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_retreat_pages(): void
    {
        foreach ($this->retreatRoutes() as $routeName) {
            $this->get(route($routeName))->assertRedirect(route('login'));
        }

        $this->get(route('retreat.routes.show', 1))->assertRedirect(route('login'));
        $this->get(route('retreat.groups.create', ['route' => 1]))->assertRedirect(route('login'));
        $this->get(route('retreat.groups.show', 1))->assertRedirect(route('login'));
    }

    public function test_admin_can_visit_all_retreat_pages(): void
    {
        $this->seed(RetreatDemoSeeder::class);
        $admin = User::where('email', 'admin@lxy.edu.cn')->firstOrFail();
        $retreatRoute = RetreatRoute::firstOrFail();
        $group = RetreatGroup::firstOrFail();

        $this->actingAs($admin);

        foreach (array_diff($this->retreatRoutes(), ['retreat.approvals']) as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('retreat.approvals'))->assertForbidden();
        $this->get(route('retreat.permissions'))->assertOk();

        $this->get(route('retreat.routes.show', $retreatRoute))->assertOk();
        $this->get(route('retreat.groups.create', ['route' => $retreatRoute]))->assertOk();
        $this->get(route('retreat.groups.show', $group))->assertOk();
    }

    public function test_teacher_cannot_open_approval_center(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)
            ->get(route('retreat.approvals'))
            ->assertForbidden();
    }

    /**
     * @return list<string>
     */
    private function retreatRoutes(): array
    {
        return [
            'retreat.routes',
            'retreat.routes.create',
            'retreat.groups',
            'retreat.approvals',
        ];
    }
}
