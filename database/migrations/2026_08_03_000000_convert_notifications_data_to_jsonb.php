<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Check if we're using PostgreSQL (we always are in production for this app)
        if (config('database.default') === 'pgsql') {
            // Convert the data column from text to jsonb
            // The USING clause casts existing text values to jsonb, which works for valid JSON strings
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');
        }
    }

    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            // Convert back from jsonb to text
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
        }
    }
};
