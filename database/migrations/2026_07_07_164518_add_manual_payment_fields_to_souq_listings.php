<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('souq_listings', function (Blueprint $table): void {
            $table->string('payment_source', 20)->nullable()->after('paystack_reference');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_source');
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_submitted_at');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('souq_listings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn(['payment_source', 'payment_submitted_at', 'payment_verified_at']);
        });
    }
};
