<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_member_profile_does_not_crash()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk();
    }

    public function test_user_without_member_profile_sees_fallback_message()
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertSee('Membership details unavailable');
    }

    public function test_user_with_member_profile_sees_card()
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Aisha Cardholder',
            'onboarding_status' => 'active',
        ]);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSee('Aisha Cardholder')
            ->assertSee('TMC Member')
            ->assertDontSee('Membership details unavailable');
    }
}
