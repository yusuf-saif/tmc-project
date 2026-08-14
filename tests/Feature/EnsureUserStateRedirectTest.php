<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserStateRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_status_passes_through_to_home(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Paid Member',
            'onboarding_status' => 'member',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Home');
    }

    public function test_active_status_passes_through_to_home(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Active Free User',
            'onboarding_status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Home');
    }

    public function test_onboarding_status_redirects_to_signup(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'onboarding',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Onboarding User',
            'onboarding_status' => 'onboarding',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('membership.signup'));
    }

    public function test_registered_status_redirects_to_signup(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'registered',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Registered User',
            'onboarding_status' => 'registered',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('membership.signup'));
    }

    public function test_pending_onboarding_home_redirects_to_signup_not_login(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'pending_onboarding',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Pending Onboarding Member',
            'onboarding_status' => 'pending_onboarding',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('membership.signup'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_pending_onboarding_login_redirects_to_signup_not_login(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'pending_onboarding',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Pending Onboarding Member',
            'onboarding_status' => 'pending_onboarding',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('membership.signup'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_suspended_user_redirected_to_payment_not_logged_out(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'suspended',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Suspended Member',
            'onboarding_status' => 'suspended',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect(route('membership.payment'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_manually_suspended_user_with_session_is_logged_out(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'suspended',
            'suspended_reason' => 'Repeated community guideline breaches.',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Banned Member',
            'onboarding_status' => 'member',
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_suspended_user_can_access_payment_page(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'suspended',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Suspended Member',
            'onboarding_status' => 'suspended',
        ]);

        $response = $this->actingAs($user)->get(route('membership.payment'));

        $response->assertOk();
    }

    public function test_no_profile_with_active_status_passes_through(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('member');

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Home');
    }

    public function test_admin_bypasses_all_redirects(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
    }
}
