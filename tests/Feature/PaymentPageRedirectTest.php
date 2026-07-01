<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentPageRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_status_redirects_on_mount(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Test Member',
            'onboarding_status' => 'member',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Membership\PaymentPage::class)
            ->assertRedirect(route('home'));
    }

    public function test_onboarding_status_shows_payment_page(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        $user->memberProfile()->create([
            'display_name' => 'Test Member',
            'onboarding_status' => 'onboarding',
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Membership\PaymentPage::class)
            ->assertOk()
            ->assertSet('paymentStatus', 'onboarding');
    }
}
