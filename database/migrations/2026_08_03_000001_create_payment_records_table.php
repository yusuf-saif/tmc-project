<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('external_reference')->nullable()->unique();
            $table->string('provider')->nullable()->index();
            $table->string('billing_cycle')->nullable();
            $table->string('channel')->nullable();
            $table->unsignedBigInteger('amount_kobo')->nullable();
            $table->string('currency', 8)->default('NGN');
            $table->string('status')->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_records');
    }
};
