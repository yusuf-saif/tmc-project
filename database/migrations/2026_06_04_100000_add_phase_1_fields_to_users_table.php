<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('password');
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->text('suspended_reason')->nullable()->after('suspended_at');
            $table->string('referral_code', 8)->unique()->nullable()->after('suspended_reason');
            $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by');
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['status', 'suspended_at', 'suspended_reason', 'referral_code']);
        });
    }
};
