<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->string('wechat_qr_code_path')->nullable()->after('attachment_name');
            $table->string('wechat_qr_code_name')->nullable()->after('wechat_qr_code_path');
        });
    }

    public function down(): void
    {
        Schema::table('retreat_groups', function (Blueprint $table) {
            $table->dropColumn(['wechat_qr_code_path', 'wechat_qr_code_name']);
        });
    }
};
