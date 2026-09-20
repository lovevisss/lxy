<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CasAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cas.enabled' => true,
            'cas.server_url' => 'https://cas.example.edu/cas',
            'cas.service_url' => 'https://retreat.example.edu/auth/cas/callback',
            'database.connections.middata' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('middata');

        Schema::connection('middata')->create('t_cx_zzqxryxx', function (Blueprint $table) {
            $table->string('xgh')->nullable();
            $table->string('xm')->nullable();
            $table->string('rylx', 1)->nullable();
            $table->string('dwmc')->nullable();
            $table->string('dwbm')->nullable();
            $table->string('dzyx')->nullable();
            $table->string('yddh')->nullable();
        });
    }

    public function test_eligible_teacher_can_sign_in_through_cas_and_sync_directory_profile(): void
    {
        DB::connection('middata')->table('t_cx_zzqxryxx')->insert([
            'xgh' => 'T2026001',
            'xm' => '测试教师',
            'rylx' => '1',
            'dwmc' => '信息工程学院',
            'dwbm' => 'D001',
            'dzyx' => null,
            'yddh' => '13800000006',
        ]);
        Http::fake([
            'https://cas.example.edu/cas/serviceValidate*' => Http::response($this->successXml(), 200),
        ]);

        $this->withSession(['cas.intended' => '/groups'])
            ->get(route('auth.cas.callback', ['ticket' => 'ST-VALID']))
            ->assertRedirect('/groups');

        $user = User::where('staff_number', 'T2026001')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('测试教师', $user->name);
        $this->assertSame('信息工程学院', $user->department);
        $this->assertSame('13800000006', $user->mobile);
        $this->assertSame('cas', $user->identity_source);
        $this->assertTrue($user->retreat_eligible);
        $this->assertSame('T2026001', $user->cas_subject);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://cas.example.edu/cas/serviceValidate?')
            && $request['service'] === 'https://retreat.example.edu/auth/cas/callback'
            && $request['ticket'] === 'ST-VALID');
    }

    public function test_non_teacher_cas_account_is_rejected_by_eligibility_list(): void
    {
        DB::connection('middata')->table('t_cx_zzqxryxx')->insert([
            'xgh' => 'S2026001',
            'xm' => '测试学生',
            'rylx' => '0',
            'dwmc' => '信息工程学院',
        ]);
        Http::fake([
            'https://cas.example.edu/cas/serviceValidate*' => Http::response(
                str_replace('T2026001', 'S2026001', $this->successXml()),
                200,
            ),
        ]);

        $this->get(route('auth.cas.callback', ['ticket' => 'ST-STUDENT']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('cas');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['staff_number' => 'S2026001']);
    }

    public function test_teacher_sync_command_imports_the_eligible_directory(): void
    {
        DB::connection('middata')->table('t_cx_zzqxryxx')->insert([
            [
                'xgh' => 'T2026001',
                'xm' => '教师甲',
                'rylx' => '1',
                'dwmc' => '人文学院',
                'yddh' => '13800000007',
            ],
            [
                'xgh' => 'T2026002',
                'xm' => '教师乙',
                'rylx' => '1',
                'dwmc' => '理学院',
                'yddh' => null,
            ],
            [
                'xgh' => 'S2026001',
                'xm' => '学生甲',
                'rylx' => '0',
                'dwmc' => '理学院',
                'yddh' => null,
            ],
        ]);

        $this->artisan('retreat:sync-teachers')
            ->expectsOutput('已同步 2 名教师，停用 0 个失效资格账号。')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'staff_number' => 'T2026001',
            'name' => '教师甲',
            'retreat_eligible' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'staff_number' => 'T2026002',
            'name' => '教师乙',
            'retreat_eligible' => true,
        ]);
        $this->assertDatabaseMissing('users', ['staff_number' => 'S2026001']);
    }

    public function test_cas_single_logout_clears_the_local_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('auth.cas.slo', ['callback' => 'authxLogout']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=UTF-8')
            ->assertSee('authxLogout({"success":true});', false);

        $this->assertGuest();
    }

    private function successXml(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
                <cas:authenticationSuccess>
                    <cas:user>T2026001</cas:user>
                    <cas:attributes>
                        <cas:accountId>T2026001</cas:accountId>
                        <cas:userName>测试教师</cas:userName>
                    </cas:attributes>
                </cas:authenticationSuccess>
            </cas:serviceResponse>
            XML;
    }
}
