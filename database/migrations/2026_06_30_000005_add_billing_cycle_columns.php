<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->timestamp('current_period_ends_at')->nullable()->after('grace_period_ends_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('current_period_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['current_period_ends_at', 'reminder_sent_at']);
        });
    }
};
