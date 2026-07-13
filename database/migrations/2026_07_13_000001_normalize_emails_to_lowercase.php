<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Deduplicate users whose emails differ only by case.
        // PostgreSQL unique constraints are case-sensitive, so
        // "User@Ex.com" and "user@ex.com" are separate rows.
        // We keep the lower-ID (older) account and delete the rest.
        $duplicates = DB::select('
            SELECT u1.id
            FROM users u1
            INNER JOIN users u2
                ON LOWER(u1.email) = LOWER(u2.email)
                AND u1.id > u2.id
        ');

        if ($duplicates !== []) {
            $ids = array_column($duplicates, 'id');
            DB::table('users')->whereIn('id', $ids)->delete();
        }

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
