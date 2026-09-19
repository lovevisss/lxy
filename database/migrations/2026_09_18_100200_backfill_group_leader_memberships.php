<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('retreat_groups')
            ->select(['id', 'leader_id'])
            ->orderBy('id')
            ->each(function (object $group) use ($now): void {
                DB::table('retreat_group_applications')->updateOrInsert(
                    [
                        'retreat_group_id' => $group->id,
                        'user_id' => $group->leader_id,
                    ],
                    [
                        'member_count' => 1,
                        'family_members' => json_encode([], JSON_UNESCAPED_UNICODE),
                        'message' => '团长创建组团时自动加入',
                        'status' => 'approved',
                        'reviewed_by' => $group->leader_id,
                        'reviewed_at' => $now,
                        'review_comment' => '系统自动确认团长成员资格',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            });
    }

    public function down(): void
    {
        // Memberships are retained because they represent real group members.
    }
};
