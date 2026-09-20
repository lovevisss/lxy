<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->text('status_reason')->nullable()->after('status');
            $table->timestamp('formed_at')->nullable()->after('status_reason');
            $table->timestamp('failed_at')->nullable()->after('formed_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
            $table->timestamp('final_confirmation_deadline')->nullable()->after('cancelled_at');
        });

        Schema::table('retreat_group_applications', function (Blueprint $table) {
            $table->string('contact_mobile', 20)->nullable()->after('message');
            $table->string('final_confirmation_status')->default('not_required')->index()->after('status');
            $table->timestamp('final_confirmation_at')->nullable()->after('final_confirmation_status');
        });

        Schema::create('retreat_sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retreat_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retreat_group_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mobile', 20)->nullable();
            $table->string('event')->index();
            $table->text('content');
            $table->string('status')->default('pending_provider')->index();
            $table->string('provider')->default('china_mobile');
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retreat_sms_notifications');

        Schema::table('retreat_group_applications', function (Blueprint $table) {
            $table->dropColumn(['contact_mobile', 'final_confirmation_status', 'final_confirmation_at']);
        });

        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->dropColumn([
                'status_reason',
                'formed_at',
                'failed_at',
                'cancelled_at',
                'final_confirmation_deadline',
            ]);
        });
    }
};
