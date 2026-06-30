<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jannah_coins_ledger', function (Blueprint $table) {
            $table->string('reason', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('jannah_coins_ledger', function (Blueprint $table) {
            $table->enum('reason', ['onboarding', 'referral', 'manual', 'admin_adjustment'])->change();
        });
    }
};
