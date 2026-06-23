<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->string('preferred_billing_cycle', 20)->nullable()->after('membership_type');
            $table->timestamp('next_due_at')->nullable()->after('activated_at');
            $table->string('paystack_reference')->nullable()->after('next_due_at');
            $table->string('paystack_customer_code')->nullable()->after('paystack_reference');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_billing_cycle',
                'next_due_at',
                'paystack_reference',
                'paystack_customer_code',
            ]);
        });
    }
};
