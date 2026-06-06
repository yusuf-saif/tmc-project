<?php

namespace Database\Seeders;

use App\Models\CommunitySpace;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $spaces = [
            [
                'name' => 'TMC Monthly Discussions',
                'short_description' => 'Our signature monthly halaqah circle',
                'description' => 'A monthly gathering where sisters come together for reflection, discussion, and warm connection in a calm space.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'SISTEEN Space',
                'short_description' => 'A safe circle for teen Muslimahs',
                'description' => 'A youth-friendly space for teenage sisters to ask questions, build confidence, and grow together with care.',
                'is_youth_space' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'General Reflections',
                'short_description' => 'Daily reminders and community reflections',
                'description' => 'An open space for sharing gentle reminders, everyday reflections, and moments that uplift the wider sisterhood.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($spaces as $space) {
            CommunitySpace::query()->updateOrCreate(
                ['name' => $space['name']],
                $space,
            );
        }
    }
}
