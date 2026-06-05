<?php

namespace Database\Seeders;

use App\Models\SouqListing;
use App\Models\User;
use Illuminate\Database\Seeder;

class SouqSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->first()?->id;

        if (! $userId) {
            return;
        }

        $listings = [
            [
                'business_name' => 'Noor Threads',
                'category' => 'fashion',
                'description' => 'Modest fashion designed for the modern Muslimah.',
                'contact_email' => 'noor@example.com',
                'status' => 'approved',
            ],
            [
                'business_name' => 'Barakah Bakes',
                'category' => 'food_catering',
                'description' => 'Halal celebration cakes and catering in the community.',
                'contact_email' => 'barakah@example.com',
                'status' => 'approved',
            ],
            [
                'business_name' => 'Sakina Wellness',
                'category' => 'health_beauty',
                'description' => 'Natural skincare rooted in Sunnah ingredients.',
                'contact_email' => 'sakina@example.com',
                'status' => 'approved',
            ],
        ];

        foreach ($listings as $listing) {
            SouqListing::query()->updateOrCreate(
                ['business_name' => $listing['business_name']],
                $listing + [
                    'user_id' => $userId,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                ],
            );
        }
    }
}
