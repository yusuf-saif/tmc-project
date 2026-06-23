<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_onboarding_drafts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('payload');
            $table->integer('step')->default(1);
            $table->string('status')->default('draft');
            $table->string('referral_code')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_onboarding_drafts');
    }
};
