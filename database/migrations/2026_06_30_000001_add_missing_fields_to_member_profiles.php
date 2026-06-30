<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('user_id');
            $table->string('avatar_path')->nullable()->after('tiktok_username');
            $table->json('notification_preferences')->nullable()->after('avatar_path');
            $table->json('goals')->nullable()->after('notification_preferences');
            $table->timestamp('onboarding_completed_at')->nullable()->after('goals');
            $table->integer('membership_serial')->nullable()->after('membership_id');
            $table->string('payment_status', 20)->nullable()->after('payment_source');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'avatar_path',
                'notification_preferences',
                'goals',
                'onboarding_completed_at',
                'membership_serial',
                'payment_status',
            ]);
        });
    }
};
