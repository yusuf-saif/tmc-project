<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_serials', function (Blueprint $table) {
            $table->id();
            $table->string('membership_type', 10);
            $table->integer('hijri_year');
            $table->integer('last_serial')->default(0);
            $table->timestamps();

            $table->unique(['membership_type', 'hijri_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_serials');
    }
};
