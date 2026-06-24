<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PaymentRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);
    }

    public function test_stale_payment_processing_is_recovered(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'payment_processing',
            'payment_submitted_at' => now()->subHours(2),
            'preferred_billing_cycle' => 'monthly',
        ]);

        $exitCode = Artisan::call('membership:recover-stale-payments');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Recovered 1', $output);

        $profile->refresh();
        $this->assertEquals('payment_failed', $profile->onboarding_status);
        $this->assertStringContainsString('timed out', $profile->payment_failed_reason);
    }

    public function test_recent_payment_processing_is_not_recovered(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'payment_processing',
            'payment_submitted_at' => now()->subMinutes(5),
            'preferred_billing_cycle' => 'monthly',
        ]);

        $exitCode = Artisan::call('membership:recover-stale-payments');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('No stale', $output);

        $profile->refresh();
        $this->assertEquals('payment_processing', $profile->onboarding_status);
    }

    public function test_active_payments_are_not_affected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'active',
            'payment_submitted_at' => now()->subDays(2),
            'preferred_billing_cycle' => 'monthly',
        ]);

        $exitCode = Artisan::call('membership:recover-stale-payments');

        $this->assertEquals(0, $exitCode);

        $profile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);
    }
}
