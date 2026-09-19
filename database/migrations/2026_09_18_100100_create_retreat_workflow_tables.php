<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retreat_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users');
            $table->string('title');
            $table->string('region')->nullable();
            $table->string('location');
            $table->text('summary');
            $table->unsignedSmallInteger('days');
            $table->unsignedSmallInteger('min_people');
            $table->unsignedSmallInteger('max_people');
            $table->string('departure_city');
            $table->string('return_city');
            $table->string('inbound_transport')->nullable();
            $table->string('outbound_transport')->nullable();
            $table->json('highlights')->nullable();
            $table->text('experiences')->nullable();
            $table->text('hotel_standard');
            $table->text('meal_standard');
            $table->text('local_transport');
            $table->text('ticket_standard');
            $table->text('guide_service');
            $table->text('insurance');
            $table->json('value_added')->nullable();
            $table->json('notices')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('current_stage')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('retreat_itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_route_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->string('title');
            $table->string('location');
            $table->string('transport')->nullable();
            $table->text('morning')->nullable();
            $table->text('afternoon')->nullable();
            $table->text('evening')->nullable();
            $table->text('plan')->nullable();
            $table->string('meals')->nullable();
            $table->string('stay')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['retreat_route_id', 'day_number']);
        });

        Schema::create('retreat_route_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users');
            $table->string('stage');
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('retreat_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_route_id')->constrained();
            $table->foreignId('leader_id')->constrained('users');
            $table->string('title');
            $table->date('departure_date');
            $table->date('return_date');
            $table->date('application_deadline');
            $table->unsignedSmallInteger('min_people');
            $table->unsignedSmallInteger('max_people');
            $table->text('meeting_info')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });

        Schema::create('retreat_group_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->unsignedSmallInteger('member_count')->default(1);
            $table->json('family_members')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamps();
            $table->unique(['retreat_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_group_applications');
        Schema::dropIfExists('retreat_groups');
        Schema::dropIfExists('retreat_route_approvals');
        Schema::dropIfExists('retreat_itinerary_days');
        Schema::dropIfExists('retreat_routes');
    }
};
