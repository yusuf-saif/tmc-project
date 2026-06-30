<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_profiles');
    }

    public function down(): void
    {
        // Data has been migrated to member_profiles; table should not be recreated.
    }
};
