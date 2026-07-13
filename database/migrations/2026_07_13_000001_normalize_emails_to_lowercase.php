<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereRaw('email != LOWER(email)')
            ->update(['email' => DB::raw('LOWER(email)')]);

        DB::table('password_reset_tokens')
            ->whereRaw('email != LOWER(email)')
            ->update(['email' => DB::raw('LOWER(email)')]);

        DB::table('support_applications')
            ->whereRaw('email != LOWER(email)')
            ->update(['email' => DB::raw('LOWER(email)')]);

        DB::table('souq_listings')
            ->whereRaw('contact_email != LOWER(contact_email)')
            ->update(['contact_email' => DB::raw('LOWER(contact_email)')]);
    }

    public function down(): void
    {
        // Data-only migration — no revert needed.
    }
};
