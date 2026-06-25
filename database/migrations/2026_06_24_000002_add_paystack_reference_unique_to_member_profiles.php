<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('member_profiles', 'member_profiles_paystack_reference_unique')) {
            Schema::table('member_profiles', function (Blueprint $table) {
                $table->unique('paystack_reference');
            });
        }
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropUnique(['paystack_reference']);
        });
    }
};
