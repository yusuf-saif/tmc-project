<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('email_sent_at');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->timestamp('under_review_email_sent_at')->nullable()->after('needs_correction_notes');
            $table->timestamp('approval_email_sent_at')->nullable()->after('under_review_email_sent_at');
            $table->timestamp('payment_confirmed_email_sent_at')->nullable()->after('approval_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn('under_review_email_sent_at');
            $table->dropColumn('approval_email_sent_at');
            $table->dropColumn('payment_confirmed_email_sent_at');
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->timestamp('email_sent_at')->nullable();
        });
    }
};
