<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('external_reference');

            $table->unique('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::table('payment_records', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);

            $table->dropColumn('idempotency_key');
        });
    }
};
