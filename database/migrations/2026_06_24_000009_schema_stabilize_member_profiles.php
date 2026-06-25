<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('member_profiles', 'next_due_at')) {
                $table->timestamp('next_due_at')->nullable()->after('activated_at');
            }

            if (! Schema::hasColumn('member_profiles', 'preferred_billing_cycle')) {
                $table->string('preferred_billing_cycle', 20)->nullable()->after('membership_type');
            }

            if (! Schema::hasColumn('member_profiles', 'paystack_reference')) {
                $table->string('paystack_reference')->nullable()->after('paystack_customer_code');
            }

            if (! Schema::hasColumn('member_profiles', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_submitted_at');
            }
        });
    }

    public function down(): void
    {
        // No-op: we cannot safely drop columns in a rollback
        // without knowing which ones we added.
    }
};
