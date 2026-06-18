<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('location_country')->default('Nigeria');
            $table->string('location_state')->nullable();
            $table->string('location_international')->nullable();
            $table->string('age_group')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('phone')->nullable();
            $table->string('ig_username')->nullable();
            $table->string('fb_username')->nullable();
            $table->string('x_username')->nullable();
            $table->string('tiktok_username')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->string('onboarding_status')->default('draft');
            $table->string('membership_type', 10)->nullable();
            $table->string('membership_id')->nullable()->unique();
            $table->integer('hijri_year')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
