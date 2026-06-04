<?php

namespace Database\Seeders;

use App\Models\Interest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InterestSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'Qur\'an',
            'Du\'a',
            'Motherhood',
            'Sisterhood',
            'Business',
            'Wellbeing',
            'Marriage',
            'Career',
            'Volunteering',
            'Education',
        ] as $index => $name) {
            Interest::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }
}
