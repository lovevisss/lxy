<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_routes', function (Blueprint $table): void {
            $table->string('provider_name', 100)->nullable()->after('retreat_route_import_id');
        });

        DB::table('retreat_routes')
            ->whereIn('retreat_route_import_id', function ($query): void {
                $query->select('id')
                    ->from('retreat_route_imports')
                    ->where('original_filename', 'like', '康远标段一 PDF 线路%');
            })
            ->update(['provider_name' => '康远国际旅行社']);
    }

    public function down(): void
    {
        Schema::table('retreat_routes', function (Blueprint $table): void {
            $table->dropColumn('provider_name');
        });
    }
};
