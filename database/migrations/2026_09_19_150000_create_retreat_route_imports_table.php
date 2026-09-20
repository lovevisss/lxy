<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retreat_route_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_filename');
            $table->string('stored_path')->nullable();
            $table->string('status')->default('processing')->index();
            $table->unsignedInteger('total_routes')->default(0);
            $table->unsignedInteger('imported_routes')->default(0);
            $table->unsignedInteger('failed_routes')->default(0);
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        Schema::table('retreat_routes', function (Blueprint $table) {
            $table->foreignId('retreat_route_import_id')
                ->nullable()
                ->after('creator_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('retreat_routes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('retreat_route_import_id');
        });

        Schema::dropIfExists('retreat_route_imports');
    }
};
