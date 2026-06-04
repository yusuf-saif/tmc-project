<?php

namespace Database\Seeders;

use App\Models\Goal;
use Illuminate\Database\Seeder;

class GoalSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Community', 'slug' => 'community'],
            ['name' => 'Learning', 'slug' => 'learning'],
            ['name' => 'Business', 'slug' => 'business'],
            ['name' => 'Volunteering', 'slug' => 'volunteering'],
        ] as $goal) {
            Goal::updateOrCreate(
                ['slug' => $goal['slug']],
                [
                    'name' => $goal['name'],
                    'is_active' => true,
                ],
            );
        }
    }
}
