<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['super_admin', 'admin', 'moderator', 'content_editor', 'volunteer', 'member'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
