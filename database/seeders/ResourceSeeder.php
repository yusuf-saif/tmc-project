<?php

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->first()?->id;

        if (! $userId) {
            return;
        }

        $resources = [
            [
                'title' => "Du'a for Beginning",
                'description' => 'Begin every action with the name of Allah.',
                'category' => 'dua_book',
                'type' => 'dua',
                'body' => 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
                'status' => 'published',
            ],
            [
                'title' => 'Finding Sakīnah in the Chaos',
                'description' => 'A reflection on finding calm through dhikr.',
                'category' => 'dear_allah',
                'type' => 'article',
                'body' => 'In the rush of daily life, tranquility begins with returning the heart to remembrance.',
                'status' => 'published',
            ],
            [
                'title' => 'Morning Adhkar Guide',
                'description' => 'Your daily morning remembrance checklist.',
                'category' => 'pocket_guide',
                'type' => 'guide',
                'status' => 'published',
            ],
            [
                'title' => 'TMC Halaqah — Gratitude in Islam',
                'description' => 'A recorded session on shukr.',
                'category' => 'audio_halaqahs',
                'type' => 'video_link',
                'external_url' => 'https://www.youtube.com/watch?v=example',
                'status' => 'published',
            ],
        ];

        foreach ($resources as $resource) {
            Resource::query()->updateOrCreate(
                ['title' => $resource['title']],
                $resource + [
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ],
            );
        }
    }
}
