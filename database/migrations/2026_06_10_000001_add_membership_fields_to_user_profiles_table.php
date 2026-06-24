<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // All user_profiles columns are now defined in
        // 2026_06_04_100100_create_user_profiles_table.
        // This migration is kept to preserve the migration chain.
    }

    public function down(): void
    {
        //
    }
};
