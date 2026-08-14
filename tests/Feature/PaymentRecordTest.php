<?php

namespace Tests\Feature;

use App\Models\PaymentRecord;
use App\Models\Setting;
use App\Models\User;
use App\Services\MembershipStateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRecordTest extends TestCase
{
    use RefreshDatabase;

    protected MembershipStateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);

        $this->service = app(MembershipStateService::class);

        Setting::create(['key' => 'membership_fee_monthly', 'value' => '5000']);
        Setting::create(['key' => 'membership_fee_quarterly', 'value' => '12000']);
        Setting::create(['key' => 'membership_fee_yearly', 'value' => '40000']);
        Setting::create(['key' => 'membership_billing_cycle_days', 'value' => '30']);
    }

    protected function createUserWithProfile(string $status = 'onboarding'): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => $status]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => $status,
                'preferred_billing_cycle' => 'monthly',
            ],
        );

        return $user->fresh();
    }

    public function test_record_payment_creates_paid_manual_record(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->recordPayment($profile, $user, 'monthly');

        $this->assertDatabaseHas('payment_records', [
            'id' => $record->id,
            'user_id' => $user->id,
            'member_profile_id' => $profile->id,
            'provider' => 'manual',
            'status' => 'paid',
            'amount_kobo' => 500000,
            'currency' => 'NGN',
        ]);

        $this->assertNotNull($record->paid_at);
        $this->assertEquals('member', $profile->fresh()->onboarding_status);
    }

    public function test_record_payment_marks_existing_pending_record_paid(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $pending = PaymentRecord::query()->create([
            'user_id' => $user->id,
            'member_profile_id' => $profile->id,
            'external_reference' => 'TMC-REF-001',
            'provider' => 'paystack',
            'status' => 'pending',
        ]);

        $record = $this->service->recordPayment($profile, $user, 'monthly', $pending);

        $this->assertEquals('paid', $record->status);
        $this->assertEquals('TMC-REF-001', $record->external_reference);
        $this->assertEquals(500000, $record->amount_kobo);
    }

    public function test_record_payment_keeps_existing_amount(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $pending = PaymentRecord::query()->create([
            'user_id' => $user->id,
            'member_profile_id' => $profile->id,
            'external_reference' => 'TMC-REF-002',
            'provider' => 'paystack',
            'amount_kobo' => 400000,
            'status' => 'pending',
        ]);

        $record = $this->service->recordPayment($profile, $user, 'monthly', $pending);

        $this->assertEquals('paid', $record->status);
        $this->assertEquals(400000, $record->amount_kobo);
    }

    public function test_find_or_create_reuses_record_by_reference(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-REF-003', 'paystack');
        $again = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-REF-003', 'paystack');

        $this->assertSame($record->id, $again->id);
        $this->assertSame(1, PaymentRecord::query()->where('external_reference', 'TMC-REF-003')->count());
    }

    public function test_record_payment_creates_record_when_reference_omitted(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile);

        $this->assertNull($record->external_reference);
        $this->assertEquals('pending', $record->status);
    }

    public function test_monthly_payment_sets_30_day_period(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-MONTHLY', 'paystack', 'monthly');
        $this->service->recordPayment($profile, $user, 'monthly', $record);

        $profile->refresh();
        $this->assertNotNull($profile->current_period_ends_at);
        $days = now()->diffInDays($profile->current_period_ends_at, absolute: true);
        $this->assertEqualsWithDelta(30, $days, 1);
    }

    public function test_quarterly_payment_sets_90_day_period(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-QUARTERLY', 'paystack', 'quarterly');
        $this->service->recordPayment($profile, $user, 'quarterly', $record);

        $profile->refresh();
        $this->assertNotNull($profile->current_period_ends_at);
        $days = now()->diffInDays($profile->current_period_ends_at, absolute: true);
        $this->assertEqualsWithDelta(90, $days, 1);
    }

    public function test_yearly_payment_sets_365_day_period(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-YEARLY', 'paystack', 'yearly');
        $this->service->recordPayment($profile, $user, 'yearly', $record);

        $profile->refresh();
        $this->assertNotNull($profile->current_period_ends_at);
        $days = now()->diffInDays($profile->current_period_ends_at, absolute: true);
        $this->assertEqualsWithDelta(365, $days, 1);
    }

    public function test_manual_confirmation_reactivates_suspended_member(): void
    {
        $user = $this->createUserWithProfile('suspended');
        $profile = $user->memberProfile;
        $profile->grace_period_ends_at = now()->subDay();
        $profile->save();

        $this->assertEquals('suspended', $profile->onboarding_status);

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, provider: 'manual', billingCycle: 'monthly');
        $this->service->recordPayment($profile, $user, 'monthly', $record);

        $profile->refresh();
        $user->refresh();
        $this->assertEquals('member', $profile->onboarding_status);
        $this->assertEquals('active', $user->status);
        $this->assertNotNull($profile->current_period_ends_at);
        $this->assertEquals('paid', $record->fresh()->status);
    }

    public function test_duplicate_record_no_ops_via_lock(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-DUP', 'paystack', 'monthly');
        $this->service->recordPayment($profile, $user, 'monthly', $record);

        $firstPeriodEnd = $profile->fresh()->current_period_ends_at;
        $firstPaidAt = $profile->fresh()->payment_verified_at;

        $this->service->recordPayment($profile, $user, 'monthly', $record);

        $profile->refresh();
        $this->assertEquals($firstPeriodEnd->timestamp, $profile->current_period_ends_at->timestamp);
        $this->assertEquals($firstPaidAt->timestamp, $profile->payment_verified_at->timestamp);
        $this->assertEquals('paid', $record->fresh()->status);
    }

    public function test_billing_cycle_stored_immutable_on_record(): void
    {
        $user = $this->createUserWithProfile();
        $profile = $user->memberProfile;

        $record = $this->service->findOrCreatePaymentRecord($user, $profile, 'TMC-IMMUT', 'paystack', 'yearly');
        $this->assertEquals('yearly', $record->billing_cycle);

        $profile->preferred_billing_cycle = 'monthly';
        $profile->save();

        $this->service->recordPayment($profile, $user, 'monthly', $record);

        $record->refresh();
        $this->assertEquals('yearly', $record->billing_cycle);

        $profile->refresh();
        $days = now()->diffInDays($profile->current_period_ends_at, absolute: true);
        $this->assertEqualsWithDelta(365, $days, 1);
    }

    public function test_double_verify_manual_submission_extends_period_once(): void
    {
        $user = $this->createUserWithProfile('onboarding');
        $profile = $user->memberProfile;
        $profile->forceFill([
            'payment_source' => 'bank_transfer',
            'payment_status' => 'pending_verification',
            'payment_submitted_at' => now(),
            'preferred_billing_cycle' => 'monthly',
        ])->saveQuietly();

        $first = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');
        $this->service->recordPayment($profile, $user, $first->billing_cycle ?? 'monthly', $first);

        $firstPeriodEnd = $profile->fresh()->current_period_ends_at;

        $second = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');
        $this->service->recordPayment($profile, $user, $second->billing_cycle ?? 'monthly', $second);

        $profile->refresh();
        $this->assertSame($first->id, $second->id);
        $this->assertEquals($firstPeriodEnd->timestamp, $profile->current_period_ends_at->timestamp);
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->where('status', 'paid')->count());
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->count());
    }

    public function test_bank_transfer_submit_reuses_existing_pending_record(): void
    {
        $user = $this->createUserWithProfile('onboarding');
        $profile = $user->memberProfile;
        $profile->forceFill(['payment_submitted_at' => now()])->saveQuietly();

        $first = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');
        $second = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->where('status', 'pending')->count());
    }

    public function test_manual_record_lookup_by_idempotency_key_returns_same_record(): void
    {
        $user = $this->createUserWithProfile('onboarding');
        $profile = $user->memberProfile;
        $profile->forceFill(['payment_submitted_at' => now()])->saveQuietly();

        $key = $this->service->manualIdempotencyKey($profile);
        $this->assertEquals('manual:'.$user->id.':'.$profile->payment_submitted_at->format('U'), $key);

        $this->service->findOrCreatePaymentRecord($user, $profile, provider: 'manual', idempotencyKey: $key);
        $again = $this->service->findOrCreatePaymentRecord($user, $profile, provider: 'manual', idempotencyKey: $key);

        $this->assertSame(1, PaymentRecord::query()->where('idempotency_key', $key)->count());
        $this->assertSame(1, PaymentRecord::query()->where('member_profile_id', $profile->id)->where('provider', 'manual')->count());
        $this->assertEquals($key, $again->idempotency_key);
    }

    public function test_new_manual_record_created_after_previous_one_is_paid(): void
    {
        $user = $this->createUserWithProfile('onboarding');
        $profile = $user->memberProfile;
        $profile->forceFill(['payment_submitted_at' => now()])->saveQuietly();

        $first = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');
        $this->service->recordPayment($profile, $user, $first->billing_cycle ?? 'monthly', $first);

        $profile->forceFill(['payment_submitted_at' => now()->addDay()])->saveQuietly();
        $second = $this->service->findOrCreateManualPaymentRecord($user, $profile, 'monthly');

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, PaymentRecord::query()->where('member_profile_id', $profile->id)->where('provider', 'manual')->count());
    }
}
