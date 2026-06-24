<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // All users columns are now defined in
        // 0001_01_01_000000_create_users_table.
        // This migration is kept to preserve the migration chain.
    }

    public function down(): void
    {
        //
    }
};
