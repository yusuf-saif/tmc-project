<?php

namespace Tests\Feature;

use App\Events\MembershipActivated;
use App\Listeners\AwardReferralCoins;
use App\Models\JannahCoinsLedger;
use App\Models\MemberProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\CoinsService;
use App\Services\MembershipStateService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CoinsIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create([
            'email' => 'admin-'.Str::random(8).'@test.com',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super_admin');

        return $user;
    }

    protected function member(): User
    {
        $user = User::factory()->create([
            'email' => 'member-'.Str::random(8).'@test.com',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('member');

        return $user;
    }

    public function test_referral_coins_not_awarded_when_amount_is_zero(): void
    {
        Setting::set('referral_coins_amount', 0);

        $referrer = $this->member();
        $referred = $this->member();
        $referred->update(['referred_by' => $referrer->id]);

        $listener = app(AwardReferralCoins::class);
        $listener->handle(new MembershipActivated($referred, 'N/A', $referred));

        $this->assertSame(0, CoinsService::getBalance($referrer));
        $this->assertDatabaseMissing('jannah_coins_ledger', [
            'user_id' => $referrer->id,
            'reason' => 'referral',
        ]);
    }

    public function test_referral_coins_not_awarded_when_amount_is_negative(): void
    {
        Setting::set('referral_coins_amount', -10);

        $referrer = $this->member();
        $referred = $this->member();
        $referred->update(['referred_by' => $referrer->id]);

        $listener = app(AwardReferralCoins::class);
        $listener->handle(new MembershipActivated($referred, 'N/A', $referred));

        $this->assertSame(0, CoinsService::getBalance($referrer));
    }

    public function test_approve_awards_coins_with_dedup_guard(): void
    {
        Setting::set('membership_approval_coins', 100);

        $admin = $this->admin();
        $member = $this->member();
        $profile = MemberProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => $member->name,
            'onboarding_status' => 'onboarding',
            'preferred_billing_cycle' => 'monthly',
        ]);

        $service = app(MembershipStateService::class);

        $this->actingAs($admin);
        $service->approve($profile, 'M', $admin);

        $this->assertSame(100, CoinsService::getBalance($member));
        $this->assertDatabaseHas('jannah_coins_ledger', [
            'user_id' => $member->id,
            'reason' => 'membership_approval',
            'reference_id' => $profile->id,
            'amount' => 100,
        ]);
    }

    public function test_approve_does_not_double_award_on_retry(): void
    {
        Setting::set('membership_approval_coins', 100);

        $admin = $this->admin();
        $member = $this->member();
        $profile = MemberProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => $member->name,
            'onboarding_status' => 'onboarding',
            'preferred_billing_cycle' => 'monthly',
        ]);

        $service = app(MembershipStateService::class);

        $this->actingAs($admin);

        $service->approve($profile, 'M', $admin);
        $service->approve($profile->fresh(), 'M', $admin);

        $this->assertSame(100, CoinsService::getBalance($member));
        $this->assertSame(1, JannahCoinsLedger::query()
            ->where('user_id', $member->id)
            ->where('reason', 'membership_approval')
            ->count());
    }

    public function test_approve_awards_zero_coins_when_setting_is_zero(): void
    {
        Setting::set('membership_approval_coins', 0);

        $admin = $this->admin();
        $member = $this->member();
        $profile = MemberProfile::query()->create([
            'user_id' => $member->id,
            'display_name' => $member->name,
            'onboarding_status' => 'onboarding',
            'preferred_billing_cycle' => 'monthly',
        ]);

        $service = app(MembershipStateService::class);

        $this->actingAs($admin);
        $service->approve($profile, 'M', $admin);

        $this->assertSame(0, CoinsService::getBalance($member));
        $this->assertDatabaseMissing('jannah_coins_ledger', [
            'user_id' => $member->id,
            'reason' => 'membership_approval',
        ]);
    }
}
