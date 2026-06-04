<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::query()->first()?->id;

        if (! $adminId) {
            return;
        }

        $events = [
            [
                'title' => 'Weekly Online Tafsir Circle',
                'description' => '<p>Join us for a gentle online tafsir circle with reflection, questions, and sisterhood.</p>',
                'location_type' => 'online',
                'location_detail' => 'Zoom gathering room',
                'event_date' => now()->addDays(7),
                'end_date' => now()->addDays(7)->addHours(2),
                'status' => 'published',
            ],
            [
                'title' => 'Community Brunch and Sisterhood Meet-up',
                'description' => '<p>An in-person brunch to reconnect, welcome new members, and spend time in uplifting company.</p>',
                'location_type' => 'in_person',
                'location_detail' => 'TMC Lounge, Downtown',
                'event_date' => now()->addDays(14),
                'end_date' => now()->addDays(14)->addHours(3),
                'status' => 'published',
            ],
            [
                'title' => 'Ramadan Reflection Replay',
                'description' => '<p>A past online reflection circle archived here as an example of recent TMC events.</p>',
                'location_type' => 'online',
                'location_detail' => 'Online session replay',
                'event_date' => now()->subDays(30),
                'end_date' => now()->subDays(30)->addHours(2),
                'status' => 'published',
            ],
        ];

        foreach ($events as $event) {
            Event::query()->updateOrCreate(
                ['title' => $event['title']],
                $event + [
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ],
            );
        }
    }
}
