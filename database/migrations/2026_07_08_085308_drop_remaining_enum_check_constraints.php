<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE jannah_coins_ledger DROP CONSTRAINT IF EXISTS jannah_coins_ledger_type_check');
        DB::statement('ALTER TABLE souq_listings DROP CONSTRAINT IF EXISTS souq_listings_category_check');
        DB::statement('ALTER TABLE souq_listings DROP CONSTRAINT IF EXISTS souq_listings_status_check');
        DB::statement('ALTER TABLE events DROP CONSTRAINT IF EXISTS events_location_type_check');
        DB::statement('ALTER TABLE events DROP CONSTRAINT IF EXISTS events_status_check');
        DB::statement('ALTER TABLE resources DROP CONSTRAINT IF EXISTS resources_type_check');
        DB::statement('ALTER TABLE resources DROP CONSTRAINT IF EXISTS resources_status_check');
        DB::statement('ALTER TABLE broadcasts DROP CONSTRAINT IF EXISTS broadcasts_target_audience_check');
        DB::statement('ALTER TABLE broadcasts DROP CONSTRAINT IF EXISTS broadcasts_status_check');
        DB::statement('ALTER TABLE newsletters DROP CONSTRAINT IF EXISTS newsletters_target_audience_check');
        DB::statement('ALTER TABLE newsletters DROP CONSTRAINT IF EXISTS newsletters_status_check');
        DB::statement('ALTER TABLE support_applications DROP CONSTRAINT IF EXISTS support_applications_type_check');
        DB::statement('ALTER TABLE support_applications DROP CONSTRAINT IF EXISTS support_applications_status_check');
    }

    public function down(): void
    {
        //
    }
};
