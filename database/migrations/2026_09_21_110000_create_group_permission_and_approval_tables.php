<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retreat_role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->string('scope_department')->nullable()->index();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        Schema::create('retreat_permission_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_name');
            $table->string('target_staff_number')->nullable();
            $table->string('actor_name');
            $table->string('action');
            $table->string('role');
            $table->string('scope_department')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->string('approval_status')->default('not_submitted')->index()->after('status');
            $table->timestamp('approval_submitted_at')->nullable()->after('approval_status');
            $table->text('approval_returned_reason')->nullable()->after('approval_submitted_at');
        });

        Schema::create('retreat_group_approval_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_group_id')->constrained()->cascadeOnDelete();
            $table->string('stage')->index();
            $table->string('scope_key');
            $table->string('department')->nullable()->index();
            $table->string('roster_hash', 64);
            $table->json('member_snapshot')->nullable();
            $table->string('status')->default('pending')->index();
            $table->boolean('active')->default(true)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['retreat_group_id', 'scope_key']);
        });

        Schema::create('retreat_group_approval_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_node_id')->nullable()->constrained('retreat_group_approval_nodes')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event')->index();
            $table->string('stage')->nullable();
            $table->string('department')->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $now = now();
        $admins = DB::table('users')->where('role', 'admin')->get(['id']);
        foreach ($admins as $admin) {
            DB::table('retreat_role_assignments')->insert([
                'user_id' => $admin->id,
                'role' => 'admin',
                'scope_department' => null,
                'granted_by' => null,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('retreat_groups')->where('status', 'formed')->update(['approval_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_group_approval_events');
        Schema::dropIfExists('retreat_group_approval_nodes');

        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'approval_submitted_at', 'approval_returned_reason']);
        });

        Schema::dropIfExists('retreat_permission_audits');
        Schema::dropIfExists('retreat_role_assignments');
    }
};
