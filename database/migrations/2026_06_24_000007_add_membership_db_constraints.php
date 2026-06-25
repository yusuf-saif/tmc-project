<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            if (! Schema::hasIndex('member_profiles', 'member_profiles_onboarding_status_index')) {
                $table->index('onboarding_status');
            }
            if (! Schema::hasColumn('member_profiles', 'payment_failed_reason')) {
                $table->text('payment_failed_reason')->nullable()->after('payment_proof_path');
            }
            if (! Schema::hasColumn('member_profiles', 'payment_source')) {
                $table->string('payment_source', 20)->nullable()->after('payment_failed_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropIndex(['onboarding_status']);
            $table->dropColumn(['payment_failed_reason', 'payment_source']);
        });
    }
};
