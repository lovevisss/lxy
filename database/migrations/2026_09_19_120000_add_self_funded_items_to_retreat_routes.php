<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_routes', function (Blueprint $table) {
            $table->json('self_funded_items')->nullable()->after('value_added');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_routes', function (Blueprint $table) {
            $table->dropColumn('self_funded_items');
        });
    }
};
