<?php

namespace Tests\Feature;

use App\Models\RetreatRoute;
use App\Models\User;
use App\Services\RetreatRoutePdfParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class RetreatRoutePdfImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_union_admin_can_parse_pdf_review_the_draft_and_keep_the_source_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create([
            'role' => 'admin',
            'retreat_eligible' => true,
        ]);
        $draft = $this->draft();
        $this->mock(RetreatRoutePdfParser::class, function (MockInterface $mock) use ($draft): void {
            $mock->shouldReceive('parse')->once()->andReturn($draft);
        });

        $response = $this->actingAs($admin)->post(route('retreat.routes.import.store'), [
            'pdf' => UploadedFile::fake()->create('长白山方案.pdf', 100, 'application/pdf'),
        ]);

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $token = $query['pdf_draft'] ?? null;
        $this->assertIsString($token);
        $response->assertRedirect(route('retreat.routes.create', ['pdf_draft' => $token]));

        $this->get($location)->assertInertia(fn (Assert $page) => $page
            ->component('retreat/CreateRoute')
            ->where('pdfDraft.title', '长白山五日疗休养')
            ->where('pdfSourceName', '长白山方案.pdf')
            ->where('pdfDraftToken', $token));

        $this->post(route('retreat.routes.store'), [
            ...$draft,
            'pdf_draft_token' => $token,
        ])->assertRedirect(route('retreat.approvals'));

        $route = RetreatRoute::where('title', '长白山五日疗休养')->firstOrFail();
        $this->assertSame('长白山方案.pdf', $route->attachment_name);
        $this->assertNotNull($route->attachment_path);
        Storage::disk('local')->assertExists($route->attachment_path);
    }

    /** @return array<string, mixed> */
    private function draft(): array
    {
        return [
            'title' => '长白山五日疗休养',
            'region' => '东北',
            'location' => '吉林·长春 / 长白山 / 延吉',
            'summary' => '自然生态与民俗文化相结合的五日疗休养线路。',
            'min_people' => 20,
            'max_people' => 25,
            'departure_city' => '杭州',
            'return_city' => '杭州',
            'inbound_transport' => '杭州-长春参考航班',
            'outbound_transport' => '长春-杭州参考航班',
            'highlights' => ['长白山天池'],
            'experiences' => '朝鲜族民俗体验',
            'hotel_standard' => '四钻酒店双人标准间',
            'meal_standard' => '含4早9正餐',
            'local_transport' => '全程空调旅游车',
            'ticket_standard' => '含行程所列首道门票',
            'guide_service' => '专业中文导游',
            'insurance' => '旅行社责任险及旅游意外险',
            'value_added' => ['每日矿泉水'],
            'self_funded_items' => ['往返机票'],
            'notices' => ['请携带有效身份证件'],
            'cover_generated' => true,
            'days' => [
                [
                    'title' => '杭州至长春',
                    'location' => '长春',
                    'transport' => '飞机',
                    'morning' => '集合出发',
                    'afternoon' => '抵达长春',
                    'evening' => '入住酒店',
                    'plan' => '',
                    'meals' => '中餐、晚餐',
                    'stay' => '长春',
                    'note' => '',
                ],
            ],
        ];
    }
}
