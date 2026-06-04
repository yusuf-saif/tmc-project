<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@themuhsinatclub.com'],
            [
                'name' => 'TMC Admin',
                'password' => Hash::make('Change1234!'),
            ],
        );

        $user->assignRole('super_admin');
    }
}
