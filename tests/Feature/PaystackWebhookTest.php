<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
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

    protected function createApprovedUser(): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'pending_review']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'payment_pending',
            'membership_id' => 'TMC-M-1447-001',
            'preferred_billing_cycle' => 'monthly',
            'approved_at' => now(),
        ]);

        return $user;
    }

    protected function generateSignature(array $payload): string
    {
        return hash_hmac('sha512', json_encode($payload), config('paystack.webhookSecret'));
    }

    public function test_verified_payment_activates_membership(): void
    {
        $user = $this->createApprovedUser();

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
        $this->assertEquals('active', $profile->onboarding_status);
        $this->assertNotNull($profile->activated_at);
        $this->assertNotNull($profile->next_due_at);
    }

    public function test_failed_payment_does_not_activate(): void
    {
        $user = $this->createApprovedUser();

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
        $user = $this->createApprovedUser();

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
        $user = $this->createApprovedUser();

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
}
