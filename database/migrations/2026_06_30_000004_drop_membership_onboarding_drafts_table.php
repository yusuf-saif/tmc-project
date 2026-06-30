<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('membership_onboarding_drafts');
    }

    public function down(): void
    {
        // Table should not be recreated; functionality has been removed.
    }
};
