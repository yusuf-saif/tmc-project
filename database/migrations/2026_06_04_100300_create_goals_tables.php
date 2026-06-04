<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('slug', ['community', 'learning', 'business', 'volunteering'])->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_goals', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();

            $table->primary(['user_id', 'goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_goals');
        Schema::dropIfExists('goals');
    }
};
