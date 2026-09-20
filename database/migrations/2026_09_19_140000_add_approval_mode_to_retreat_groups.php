<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->string('approval_mode')->default('manual')->after('max_people');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->dropColumn('approval_mode');
        });
    }
};
