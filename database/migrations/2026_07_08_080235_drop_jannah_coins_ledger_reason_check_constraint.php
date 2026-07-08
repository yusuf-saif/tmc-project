<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE jannah_coins_ledger DROP CONSTRAINT IF EXISTS jannah_coins_ledger_reason_check');
    }

    public function down(): void
    {
        //
    }
};
