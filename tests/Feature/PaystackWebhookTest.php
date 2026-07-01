<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);

        Config::set('paystack.secretKey', 'sk_test_fake');
        Config::set('paystack.webhookSecret', 'whsec_test_fake');
    }

    protected function createOnboardingUser(string $reference = 'TMC-TEST123'): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'onboarding']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'onboarding',
                'membership_id' => 'TMC-M-1447-001',
                'preferred_billing_cycle' => 'monthly',
                'paystack_reference' => $reference,
            ],
        );

        return $user;
    }

    protected function generateSignature(array $payload): string
    {
        return hash_hmac('sha512', json_encode($payload), config('paystack.webhookSecret'));
    }

    public function test_verified_payment_activates_membership(): void
    {
        $user = $this->createOnboardingUser('TMC-TEST123');

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TMC-TEST123',
                'status' => 'success',
                'amount' => 500000,
                'customer' => ['email' => $user->email],
                'metadata' => [
                    'user_id' => $user->id,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ];

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'TMC-TEST123',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'activated']);

        $user->refresh();
        $this->assertEquals('active', $user->status);

        $profile = $user->memberProfile;
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertNotNull($profile->current_period_ends_at);
    }

    public function test_failed_payment_does_not_activate(): void
    {
        $user = $this->createOnboardingUser('TMC-TEST123');

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'reference' => 'TMC-TEST123'],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TMC-TEST123',
                'status' => 'failed',
                'customer' => ['email' => $user->email],
                'metadata' => ['user_id' => $user->id, 'billing_cycle' => 'monthly'],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertStatus(500);

        $user->refresh();
        $this->assertNotEquals('active', $user->status);
        $this->assertNotEquals('active', $user->memberProfile->onboarding_status);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $payload = [
            'event' => 'charge.success',
            'data' => ['reference' => 'TMC-TEST123'],
        ];

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    public function test_missing_webhook_signature_is_rejected(): void
    {
        $payload = [
            'event' => 'charge.success',
            'data' => ['reference' => 'TMC-TEST123'],
        ];

        $response = $this->postJson(route('webhooks.paystack'), $payload, []);

        $response->assertStatus(400);
    }

    public function test_payment_amount_mismatch_is_rejected(): void
    {
        $user = $this->createOnboardingUser('TMC-TEST-UNDERPAID');

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'TMC-TEST-UNDERPAID',
                    'amount' => 100000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TMC-TEST-UNDERPAID',
                'status' => 'success',
                'amount' => 100000,
                'customer' => ['email' => $user->email],
                'metadata' => [
                    'user_id' => $user->id,
                    'billing_cycle' => 'monthly',
                ],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Payment amount does not match billing cycle']);

        $user->refresh();
        $this->assertNotEquals('active', $user->status);
    }

    public function test_webhook_is_idempotent(): void
    {
        $user = $this->createOnboardingUser('TMC-TEST123');

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'TMC-TEST123',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TMC-TEST123',
                'status' => 'success',
                'amount' => 500000,
                'customer' => ['email' => $user->email],
                'metadata' => ['user_id' => $user->id, 'billing_cycle' => 'monthly'],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response1 = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);
        $response1->assertOk();

        $response2 = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);
        $response2->assertOk();
        $response2->assertJson(['status' => 'already_verified']);
    }

    public function test_unrecognized_event_is_ignored(): void
    {
        $payload = [
            'event' => 'invoice.create',
            'data' => [],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);
        $response->assertOk();
        $response->assertJson(['status' => 'ignored']);
    }

    public function test_different_references_for_same_user_are_independent(): void
    {
        $user = $this->createOnboardingUser('TMC-DIFF-REF');

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'TMC-DIFF-REF',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'TMC-DIFF-REF',
                'status' => 'success',
                'amount' => 500000,
                'customer' => ['email' => $user->email],
                'metadata' => ['user_id' => $user->id, 'billing_cycle' => 'monthly'],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);
        $response->assertOk();
        $response->assertJson(['status' => 'activated']);

        $user->refresh();
        $profile = $user->memberProfile;
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertEquals('TMC-DIFF-REF', $profile->paystack_reference);
    }

    public function test_unmatched_reference_returns_no_profile(): void
    {
        $this->createOnboardingUser('TMC-TEST123');

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'UNMATCHED-REF',
                'status' => 'success',
                'amount' => 500000,
                'customer' => ['email' => 'nobody@test.com'],
                'metadata' => ['user_id' => 999],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'no_profile']);
    }

    public function test_non_payable_status_rejects_webhook(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'registered']);
        $user->assignRole('member');

        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'registered',
                'preferred_billing_cycle' => 'monthly',
                'paystack_reference' => 'WRONG-STATUS-REF',
            ],
        );

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'WRONG-STATUS-REF',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WRONG-STATUS-REF',
                'status' => 'success',
                'amount' => 500000,
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Profile not in approvable state']);

        $profile = $user->fresh()->memberProfile;
        $this->assertEquals('registered', $profile->onboarding_status);
    }

    public function test_webhook_accepts_onboarding_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'onboarding']);
        $user->assignRole('member');

        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'onboarding',
                'membership_id' => 'TMC-M-1447-001',
                'preferred_billing_cycle' => 'monthly',
                'paystack_reference' => 'ONBOARDING-REF',
            ],
        );

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'ONBOARDING-REF',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'ONBOARDING-REF',
                'status' => 'success',
                'amount' => 500000,
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'activated']);

        $profile = $user->fresh()->memberProfile;
        $this->assertEquals('member', $profile->onboarding_status);
    }

    public function test_selected_billing_cycle_determines_payment_amount(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'onboarding']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'onboarding',
                'membership_id' => 'TMC-M-1447-001',
                'preferred_billing_cycle' => 'yearly',
                'paystack_reference' => 'YEARLY-REF',
            ],
        );

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'YEARLY-REF',
                    'amount' => 4000000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'YEARLY-REF',
                'status' => 'success',
                'amount' => 4000000,
                'customer' => ['email' => $user->email],
                'metadata' => ['user_id' => $user->id],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'activated']);

        $profile = $user->fresh()->memberProfile;
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertNotNull($profile->current_period_ends_at);
    }

    public function test_selected_billing_cycle_rejects_lower_amount(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'onboarding']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'onboarding',
                'membership_id' => 'TMC-M-1447-001',
                'preferred_billing_cycle' => 'yearly',
                'paystack_reference' => 'UNDERPAID-YEARLY',
            ],
        );

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'UNDERPAID-YEARLY',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'UNDERPAID-YEARLY',
                'status' => 'success',
                'amount' => 500000,
                'customer' => ['email' => $user->email],
                'metadata' => ['user_id' => $user->id],
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Payment amount does not match billing cycle']);

        $user->refresh();
        $this->assertNotEquals('active', $user->status);
    }

    public function test_souq_webhook_activates_approved_unpaid_listing(): void
    {
        $user = User::factory()->create(['email' => 'souq@test.com']);
        $user->assignRole('member');

        Setting::create([
            'key' => 'souq_listing_fee',
            'value' => '50',
        ]);

        $listing = SouqListing::query()->create([
            'user_id' => $user->id,
            'business_name' => 'Souq Shop',
            'category' => 'fashion',
            'description' => 'Pay to activate',
            'contact_email' => 'shop@test.com',
            'status' => 'approved_unpaid',
            'paystack_reference' => 'SOUQ-REF-001',
            'monthly_fee' => 5000.00,
        ]);

        $payload = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'SOUQ-REF-001',
                'status' => 'success',
                'amount' => 500000,
                'metadata' => [
                    'payment_type' => 'souq_listing_fee',
                    'listing_id' => $listing->id,
                ],
            ],
        ];

        Http::fake([
            config('paystack.paymentUrl').'/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => 'SOUQ-REF-001',
                    'amount' => 500000,
                ],
            ]),
        ]);

        $signature = $this->generateSignature($payload);

        $response = $this->postJson(route('webhooks.paystack'), $payload, [
            'x-paystack-signature' => $signature,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'activated']);

        $listing->refresh();

        $this->assertSame('active', $listing->status);
        $this->assertSame('active', $listing->billing_status);
        $this->assertNotNull($listing->billing_start_date);
        $this->assertNotNull($listing->billing_end_date);
    }
}
