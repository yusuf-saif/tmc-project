<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('country')->default('Nigeria');
            $table->string('state')->nullable();
            $table->string('outside_nigeria_location')->nullable();
            $table->string('age_group')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('phone')->nullable();
            $table->string('instagram_username')->nullable();
            $table->string('facebook_username')->nullable();
            $table->string('x_username')->nullable();
            $table->string('tiktok_username')->nullable();
            $table->string('membership_id')->nullable()->unique();
            $table->string('membership_type')->nullable();
            $table->string('membership_status')->default('draft');
            $table->integer('membership_serial')->nullable();
            $table->integer('membership_hijri_year')->nullable();
            $table->timestamp('application_submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('membership_fee_paid_at')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('avatar_path')->nullable();
            $table->json('notification_preferences')->nullable();
            $table->json('goals')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
