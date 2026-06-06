<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->enum('audience_type', ['all', 'interest', 'goal', 'individual']);
            $table->json('audience_value')->nullable();
            $table->foreignId('sent_by')->constrained('users');
            $table->timestamp('sent_at');
            $table->integer('delivery_count')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
