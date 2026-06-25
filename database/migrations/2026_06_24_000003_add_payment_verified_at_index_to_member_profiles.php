<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('member_profiles', 'member_profiles_payment_verified_at_index')) {
            Schema::table('member_profiles', function (Blueprint $table) {
                $table->index('payment_verified_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropIndex(['payment_verified_at']);
        });
    }
};
