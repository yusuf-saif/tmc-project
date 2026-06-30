<?php

namespace Database\Seeders;

use App\Models\JannahCoinsLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlaywrightSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $user = User::query()->updateOrCreate(
            ['email' => 'member@test.com'],
            [
                'name' => 'Test Member',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
                'referral_code' => 'PWTEST01',
            ],
        );

        $user->syncRoles(['member']);

        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => 'Test Member',
                'first_name' => 'Test',
                'last_name' => 'Member',
                'location_country' => 'Nigeria',
                'location_state' => 'Lagos',
                'onboarding_status' => 'active',
                'membership_id' => 'TMC-M-1447-001',
                'onboarding_completed_at' => now(),
            ],
        );

        if (! JannahCoinsLedger::query()->where('user_id', $user->id)->where('reason', 'onboarding')->exists()) {
            JannahCoinsLedger::query()->create([
                'user_id' => $user->id,
                'type' => 'earned',
                'reason' => 'onboarding',
                'amount' => 50,
            ]);
        }
    }
}
