<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // All member_profiles columns are now defined in
        // 2026_06_18_000000_create_member_profiles_table.
        // This migration is kept to preserve the migration chain.
    }

    public function down(): void
    {
        //
    }
};
