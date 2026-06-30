<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\User;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterestGoalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'ADMIN02',
        ]);
        $this->admin->assignRole('admin');
        $this->admin->memberProfile()->updateOrCreate([
            'display_name' => $this->admin->name,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_admin_can_load_interests_resource_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/interests')
            ->assertOk();
    }

    public function test_admin_can_load_goals_resource_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/goals')
            ->assertOk();
    }

    public function test_interests_seeder_creates_interests(): void
    {
        $interests = Interest::all();

        $this->assertGreaterThan(0, $interests->count());
        $this->assertNotNull(Interest::where('slug', 'quran')->first());
    }

    public function test_goals_seeder_creates_goals(): void
    {
        $goals = Goal::all();

        $this->assertGreaterThan(0, $goals->count());
    }

    public function test_member_can_have_interests(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'INTRST1',
        ]);
        $user->assignRole('member');

        $interest = Interest::first();
        $user->interests()->sync([$interest->id]);

        $this->assertTrue($user->interests->contains($interest));
    }

    public function test_member_can_have_goals(): void
    {
        $user = User::factory()->create([
            'email' => 'goaluser@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'GOAL001',
        ]);
        $user->assignRole('member');

        $goal = Goal::first();
        $user->goals()->sync([$goal->id]);

        $this->assertTrue($user->goals->contains($goal));
    }

    public function test_interest_active_scope(): void
    {
        $count = Interest::active()->count();
        $total = Interest::count();

        $this->assertLessThanOrEqual($total, $count);
        $this->assertGreaterThan(0, $count);
    }

    public function test_goal_active_scope(): void
    {
        $count = Goal::active()->count();
        $total = Goal::count();

        $this->assertLessThanOrEqual($total, $count);
        $this->assertGreaterThan(0, $count);
    }
}
