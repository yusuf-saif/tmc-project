<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dismissed_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('in_app_announcement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('dismissed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'in_app_announcement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_announcements');
    }
};
