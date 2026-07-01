<?php

namespace Tests\Feature;

use App\Livewire\Membership\PaymentPage;
use App\Livewire\Souq\ApplyForm;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SouqPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Setting::set('souq_listing_fee_kobo', 500000);
        Setting::set('membership_fee_monthly', 5000);
        Setting::set('coin_value_kobo', 500);
        Setting::set('max_redemption_percent', 20);

        $this->member = User::factory()->create([
            'email' => 'souqowner@test.com',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $this->member->assignRole('member');
        $this->member->memberProfile()->create([
            'display_name' => 'Souq Owner',
            'onboarding_status' => 'active',
        ]);
    }

    public function test_pay_listing_redirects_to_external_paystack_url(): void
    {
        SouqListing::query()->create([
            'user_id' => $this->member->id,
            'business_name' => 'My Souq Shop',
            'category' => 'fashion',
            'description' => 'Test listing for payment redirect regression',
            'contact_email' => 'souqowner@test.com',
            'status' => 'approved_unpaid',
            'monthly_fee' => 5000,
        ]);

        Http::fake([
            config('paystack.paymentUrl') . '/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/fake_souq_url',
                    'reference' => 'souq_test_ref',
                    'access_code' => 'test_code',
                ],
            ], 200),
        ]);

        $component = Livewire::actingAs($this->member)
            ->test(ApplyForm::class);

        $component->call('payListing')
            ->assertRedirect('https://checkout.paystack.com/fake_souq_url');
    }

    public function test_payment_page_redirect_to_paystack_external_url(): void
    {
        $this->member->memberProfile->update(['onboarding_status' => 'onboarding']);

        Http::fake([
            config('paystack.paymentUrl') . '/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/fake_membership_url',
                    'reference' => 'mem_test_ref',
                    'access_code' => 'test_code',
                ],
            ], 200),
        ]);

        $component = Livewire::actingAs($this->member)
            ->test(PaymentPage::class);

        $component->call('redirectToPaystack')
            ->assertRedirect('https://checkout.paystack.com/fake_membership_url');
    }
}
