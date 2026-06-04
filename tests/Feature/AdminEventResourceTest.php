<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_load_events_resource_pages(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN001',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin/events')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/events/create')
            ->assertOk();
    }
}
