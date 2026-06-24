<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('bg_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('initial', 1)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Category::query()->insert([
            ['name' => "Du'a Book", 'slug' => 'dua_book', 'description' => 'Collection of duas and supplications', 'bg_color' => 'var(--teal-lt)', 'text_color' => 'var(--teal)', 'initial' => 'D', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dear Allah', 'slug' => 'dear_allah', 'description' => 'Personal reflections and letters to Allah', 'bg_color' => 'var(--gold-pale)', 'text_color' => 'var(--gold)', 'initial' => 'A', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pocket Guide', 'slug' => 'pocket_guide', 'description' => 'Quick reference guides and checklists', 'bg_color' => 'var(--plum-lt)', 'text_color' => '#3D1A47', 'initial' => 'P', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Audio & Halaqahs', 'slug' => 'audio_halaqahs', 'description' => 'Recorded sessions and audio content', 'bg_color' => 'var(--mint-lt)', 'text_color' => 'var(--teal)', 'initial' => 'H', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('resources', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained('categories')->nullOnDelete();
        });

        $categories = Category::query()->pluck('id', 'slug');
        $resources = DB::table('resources')->whereNotNull('category')->get(['id', 'category']);
        foreach ($resources as $resource) {
            if (isset($categories[$resource->category])) {
                DB::table('resources')->where('id', $resource->id)->update(['category_id' => $categories[$resource->category]]);
            }
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
        });

        $categories = DB::table('categories')->pluck('slug', 'id');
        $resources = DB::table('resources')->whereNotNull('category_id')->get(['id', 'category_id']);
        foreach ($resources as $resource) {
            if (isset($categories[$resource->category_id])) {
                DB::table('resources')->where('id', $resource->id)->update(['category' => $categories[$resource->category_id]]);
            }
        }

        Schema::table('resources', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
