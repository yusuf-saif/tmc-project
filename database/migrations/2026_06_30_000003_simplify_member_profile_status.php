<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->timestamp('first_paid_at')->nullable()->after('activated_at');
            $table->timestamp('grace_period_ends_at')->nullable()->after('next_due_at');
        });

        DB::statement("UPDATE member_profiles SET onboarding_status = 'registered' WHERE onboarding_status IN ('draft')");
        DB::statement("UPDATE member_profiles SET onboarding_status = 'onboarding' WHERE onboarding_status IN ('in_progress', 'pending_review', 'submitted', 'under_review', 'approved_pending_payment', 'payment_processing', 'payment_failed', 'needs_correction')");
        DB::statement("UPDATE member_profiles SET onboarding_status = 'active' WHERE onboarding_status IN ('approved', 'active')");
        DB::statement("UPDATE member_profiles SET onboarding_status = 'registered' WHERE onboarding_status = 'rejected'");
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['first_paid_at', 'grace_period_ends_at']);
        });
    }
};
