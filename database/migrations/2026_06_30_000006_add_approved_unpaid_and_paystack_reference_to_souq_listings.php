<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('souq_listings', function (Blueprint $table): void {
            $table->string('status', 50)->default('pending')->change();
            $table->string('paystack_reference', 100)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('souq_listings', function (Blueprint $table): void {
            $table->dropUnique(['paystack_reference']);
            $table->dropColumn('paystack_reference');
        });
    }
};
