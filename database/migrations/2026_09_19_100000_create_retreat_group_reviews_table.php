<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retreat_group_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('route_score');
            $table->unsignedTinyInteger('meal_score');
            $table->unsignedTinyInteger('attraction_score');
            $table->unsignedTinyInteger('accommodation_score');
            $table->unsignedTinyInteger('service_score');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['retreat_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_group_reviews');
    }
};
