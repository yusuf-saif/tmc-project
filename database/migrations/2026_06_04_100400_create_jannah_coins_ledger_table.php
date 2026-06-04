<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jannah_coins_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earned', 'adjusted', 'deducted']);
            $table->enum('reason', ['onboarding', 'referral', 'manual', 'admin_adjustment']);
            $table->integer('amount');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jannah_coins_ledger');
    }
};
