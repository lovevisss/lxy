<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_group_reviews', function (Blueprint $table) {
            $table->foreignId('retreat_route_id')
                ->nullable()
                ->after('retreat_group_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        DB::statement(<<<'SQL'
            UPDATE retreat_group_reviews
            SET retreat_route_id = (
                SELECT retreat_route_id
                FROM retreat_groups
                WHERE retreat_groups.id = retreat_group_reviews.retreat_group_id
            )
        SQL);

        Schema::table('retreat_group_reviews', function (Blueprint $table) {
            $table->unique(['retreat_route_id', 'user_id'], 'retreat_route_user_review_unique');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_group_reviews', function (Blueprint $table) {
            $table->dropUnique('retreat_route_user_review_unique');
            $table->dropConstrainedForeignId('retreat_route_id');
        });
    }
};
