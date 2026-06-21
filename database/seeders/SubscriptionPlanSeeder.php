<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Monthly',
                'slug' => 'monthly',
                'type' => 'monthly',
                'price' => 9.99,
                'description' => 'Pay month-to-month with no long-term commitment.',
                'features' => json_encode(['Full membership access', 'Community forum', 'Monthly newsletter']),
                'sort_order' => 1,
            ],
            [
                'name' => 'Quarterly',
                'slug' => 'quarterly',
                'type' => 'quarterly',
                'price' => 24.99,
                'description' => 'Save 17% with quarterly billing.',
                'features' => json_encode(['Full membership access', 'Community forum', 'Monthly newsletter', 'Priority support']),
                'sort_order' => 2,
            ],
            [
                'name' => 'Annual',
                'slug' => 'annual',
                'type' => 'annual',
                'price' => 79.99,
                'description' => 'Best value — save 33% with annual billing.',
                'features' => json_encode(['Full membership access', 'Community forum', 'Monthly newsletter', 'Priority support', 'Exclusive events']),
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
