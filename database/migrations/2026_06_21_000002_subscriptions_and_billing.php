<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type'); // monthly, quarterly, annual
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, expired, suspended
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->unsignedSmallInteger('hijri_start_year')->nullable();
            $table->unsignedTinyInteger('hijri_start_month')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspended_reason')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::table('souq_listings', function (Blueprint $table) {
            $table->string('billing_status')->default('none')->after('status');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete()->after('billing_status');
            $table->timestamp('billing_start_date')->nullable()->after('subscription_id');
            $table->timestamp('billing_end_date')->nullable()->after('billing_start_date');
            $table->decimal('monthly_fee', 10, 2)->default(0.00)->after('billing_end_date');
            $table->timestamp('last_billed_at')->nullable()->after('monthly_fee');
            $table->timestamp('billing_suspended_at')->nullable()->after('last_billed_at');
        });
    }

    public function down(): void
    {
        Schema::table('souq_listings', function (Blueprint $table) {
            $table->dropColumn([
                'billing_status', 'subscription_id', 'billing_start_date',
                'billing_end_date', 'monthly_fee', 'last_billed_at', 'billing_suspended_at',
            ]);
        });
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
