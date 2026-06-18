<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('display_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('nickname')->nullable()->after('last_name');
            $table->string('country')->default('Nigeria')->after('nickname');
            $table->string('state')->nullable()->after('country');
            $table->string('outside_nigeria_location')->nullable()->after('state');
            $table->string('age_group')->nullable()->after('outside_nigeria_location');
            $table->string('marital_status')->nullable()->after('age_group');
            $table->string('phone')->nullable()->after('marital_status');
            $table->string('instagram_username')->nullable()->after('phone');
            $table->string('facebook_username')->nullable()->after('instagram_username');
            $table->string('x_username')->nullable()->after('facebook_username');
            $table->string('tiktok_username')->nullable()->after('x_username');
            $table->string('membership_id')->nullable()->unique()->after('tiktok_username');
            $table->string('membership_type')->nullable()->after('membership_id');
            $table->string('membership_status')->default('draft')->after('membership_type');
            $table->integer('membership_serial')->nullable()->after('membership_status');
            $table->integer('membership_hijri_year')->nullable()->after('membership_serial');
            $table->timestamp('application_submitted_at')->nullable()->after('membership_hijri_year');
            $table->timestamp('approved_at')->nullable()->after('application_submitted_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('approved_at');
            $table->timestamp('membership_fee_paid_at')->nullable()->after('approved_by');
            $table->string('payment_status')->nullable()->after('membership_fee_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'nickname',
                'country',
                'state',
                'outside_nigeria_location',
                'age_group',
                'marital_status',
                'phone',
                'instagram_username',
                'facebook_username',
                'x_username',
                'tiktok_username',
                'membership_id',
                'membership_type',
                'membership_status',
                'membership_serial',
                'membership_hijri_year',
                'application_submitted_at',
                'approved_at',
                'membership_fee_paid_at',
                'payment_status',
            ]);

            $table->dropForeign(['approved_by']);
        });
    }
};
