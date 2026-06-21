<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('needs_correction_notes')->nullable()->after('rejection_reason');
            $table->timestamp('payment_submitted_at')->nullable()->after('needs_correction_notes');
            $table->string('payment_proof_path', 255)->nullable()->after('payment_submitted_at');
            $table->foreignId('payment_verified_by')->nullable()->after('payment_proof_path')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable()->after('payment_verified_by');
            $table->timestamp('activated_at')->nullable()->after('payment_verified_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('target_user_id')->nullable()->after('user_id');
            $table->string('performed_by_role', 50)->nullable()->after('target_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approved_at',
                'needs_correction_notes',
                'payment_submitted_at',
                'payment_proof_path',
                'payment_verified_by',
                'payment_verified_at',
                'activated_at',
            ]);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['target_user_id', 'performed_by_role']);
        });
    }
};
