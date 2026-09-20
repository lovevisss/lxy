<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('staff_number', 100)->nullable()->unique()->after('email');
            $table->string('cas_subject', 191)->nullable()->unique()->after('staff_number');
            $table->string('identity_source', 20)->default('local')->after('cas_subject');
            $table->boolean('retreat_eligible')->default(true)->after('identity_source');
            $table->string('directory_email')->nullable()->after('department');
            $table->string('mobile', 100)->nullable()->after('directory_email');
            $table->timestamp('cas_synced_at')->nullable()->after('mobile');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['staff_number']);
            $table->dropUnique(['cas_subject']);
            $table->dropColumn([
                'staff_number',
                'cas_subject',
                'identity_source',
                'retreat_eligible',
                'directory_email',
                'mobile',
                'cas_synced_at',
            ]);
        });
    }
};
