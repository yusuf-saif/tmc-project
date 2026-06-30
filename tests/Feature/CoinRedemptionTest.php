<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Livewire\Membership\PaymentPage;
use App\Livewire\Souq\ApplyForm;
use App\Models\Badge;
use App\Models\JannahCoinsLedger;
use App\Models\Setting;
use App\Models\SouqListing;
use App\Models\User;
use App\Services\CoinsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CoinRedemptionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Setting::set('coin_value_kobo', 500);
        Setting::set('max_redemption_percent', 20);

        $this->user = User::factory()->create([
            'email' => 'coinuser@test.com',
            'referral_code' => 'COIN001',
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('member');
        $this->user->memberProfile()->updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'display_name' => 'Coin User',
                'onboarding_status' => 'onboarding',
                'onboarding_completed_at' => null,
            ]
        );
    }

    protected function giveCoins(User $user, int $amount): void
    {
        CoinsService::award($user, $amount, 'onboarding', null, 'Test setup');
    }

    protected function createApprovedUnpaidListing(User $user): SouqListing
    {
        return SouqListing::create([
            'user_id' => $user->id,
            'business_name' => 'Test Business',
            'category' => 'fashion',
            'description' => 'A test listing',
            'contact_email' => $user->email,
            'status' => 'approved_unpaid',
        ]);
    }

    // ─── Redemption Calculation ─────────────────────────────────

    public function test_calculate_max_discount_respects_coin_balance(): void
    {
        $this->giveCoins($this->user, 100);

        $result = CoinsService::calculateMaxDiscount($this->user, 500000);

        $this->assertTrue($result['eligible']);
        $this->assertSame(100, $result['coins_to_use']);
        $this->assertSame(50000, $result['discount_kobo']);
        $this->assertSame(450000, $result['final_amount_kobo']);
    }

    public function test_calculate_max_discount_respects_percent_cap(): void
    {
        $this->giveCoins($this->user, 1000);

        $fullPrice = 500000;
        $result = CoinsService::calculateMaxDiscount($this->user, $fullPrice);

        $maxPercentDiscount = (int) floor($fullPrice * 0.20);
        $coinsByPercent = (int) floor($maxPercentDiscount / 500);

        $this->assertSame($coinsByPercent, $result['coins_to_use']);
        $this->assertSame($coinsByPercent * 500, $result['discount_kobo']);
        $this->assertSame($fullPrice - ($coinsByPercent * 500), $result['final_amount_kobo']);
    }

    public function test_calculate_max_discount_uses_lower_of_balance_or_cap(): void
    {
        $this->giveCoins($this->user, 30);

        $result = CoinsService::calculateMaxDiscount($this->user, 500000);

        $expectedDiscount = 30 * 500;
        $this->assertSame(30, $result['coins_to_use']);
        $this->assertSame($expectedDiscount, $result['discount_kobo']);
        $this->assertSame(500000 - $expectedDiscount, $result['final_amount_kobo']);
    }

    public function test_zero_balance_user_not_eligible_for_redemption(): void
    {
        $result = CoinsService::calculateMaxDiscount($this->user, 500000);

        $this->assertFalse($result['eligible']);
        $this->assertSame(0, $result['coins_to_use']);
        $this->assertSame(0, $result['discount_kobo']);
        $this->assertSame(500000, $result['final_amount_kobo']);
    }

    // ─── Membership Payment Toggle ──────────────────────────────

    public function test_membership_payment_shows_redemption_toggle_when_eligible(): void
    {
        $this->giveCoins($this->user, 100);

        $this->actingAs($this->user);

        $component = Livewire::test(PaymentPage::class);

        $component->assertSee('Apply your Jannah Coins');
        $component->assertSee('coins');
    }

    public function test_membership_payment_hides_toggle_when_ineligible(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(PaymentPage::class);

        $component->assertDontSee('Apply your Jannah Coins');
    }

    // ─── Souq Payment Toggle ────────────────────────────────────

    public function test_souq_payment_shows_redemption_toggle_when_eligible(): void
    {
        $this->giveCoins($this->user, 100);
        $this->createApprovedUnpaidListing($this->user);

        $this->actingAs($this->user);

        $component = Livewire::test(ApplyForm::class);

        $component->assertSee('Apply your Jannah Coins');
    }

    public function test_souq_payment_hides_toggle_when_ineligible(): void
    {
        $this->createApprovedUnpaidListing($this->user);

        $this->actingAs($this->user);

        $component = Livewire::test(ApplyForm::class);

        $component->assertDontSee('Apply your Jannah Coins');
    }

    // ─── Badge Coin Rewards ─────────────────────────────────────

    public function test_badge_award_credits_configured_coin_amount(): void
    {
        $badge = Badge::create([
            'name' => 'Test Badge',
            'description' => 'A test badge',
            'criteria' => 'Testing',
            'coin_reward' => 50,
            'is_active' => true,
        ]);

        $this->assertSame(0, CoinsService::getBalance($this->user));

        $admin = User::factory()->create(['email' => 'admin-badge@test.com', 'referral_code' => 'ADMINB1']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        UserResource::awardBadge($this->user, $badge->id);

        $this->assertSame(50, CoinsService::getBalance($this->user));
    }

    public function test_badge_with_zero_coin_reward_credits_nothing(): void
    {
        $badge = Badge::create([
            'name' => 'Zero Coin Badge',
            'description' => 'No reward',
            'criteria' => 'Testing',
            'coin_reward' => 0,
            'is_active' => true,
        ]);

        $admin = User::factory()->create(['email' => 'admin-zero@test.com', 'referral_code' => 'ADMINZ1']);
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        UserResource::awardBadge($this->user, $badge->id);

        $this->assertSame(0, CoinsService::getBalance($this->user));
    }

    // ─── Post-Payment Deduction ─────────────────────────────────

    public function test_coins_not_deducted_before_payment_confirmed(): void
    {
        $this->giveCoins($this->user, 100);

        $initialBalance = CoinsService::getBalance($this->user);
        $this->assertSame(100, $initialBalance);

        $ledgerCount = JannahCoinsLedger::where('user_id', $this->user->id)
            ->where('reason', 'like', 'redemption_%')
            ->count();
        $this->assertSame(0, $ledgerCount);
    }

    public function test_redemption_ledger_entry_tagged_with_correct_context(): void
    {
        $this->giveCoins($this->user, 100);

        CoinsService::applyRedemption($this->user, 50, 'membership', 1);

        $entry = JannahCoinsLedger::where('user_id', $this->user->id)
            ->where('reason', 'redemption_membership')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(-50, (int) $entry->amount);
        $this->assertSame(1, (int) $entry->reference_id);
    }

    public function test_souq_redemption_uses_correct_reason(): void
    {
        $this->giveCoins($this->user, 100);

        CoinsService::applyRedemption($this->user, 30, 'souq', 42);

        $entry = JannahCoinsLedger::where('user_id', $this->user->id)
            ->where('reason', 'redemption_souq')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame(-30, (int) $entry->amount);
        $this->assertSame(42, (int) $entry->reference_id);
    }

    // ─── Wallet Display ─────────────────────────────────────────

    public function test_wallet_shows_coin_value_in_naira(): void
    {
        $this->giveCoins($this->user, 200);

        $this->user->memberProfile->update([
            'onboarding_completed_at' => now(),
            'onboarding_status' => 'active',
        ]);

        $this->actingAs($this->user);

        $this->get(route('profile', ['tab' => 'wallet']))
            ->assertOk()
            ->assertSee('Jannah Coins');
    }

    // ─── Apply Redemption Deducts Coins ─────────────────────────

    public function test_apply_redemption_deducts_correct_amount(): void
    {
        $this->giveCoins($this->user, 100);

        CoinsService::applyRedemption($this->user, 40, 'membership', 1);

        $this->assertSame(60, CoinsService::getBalance($this->user));
    }

    public function test_multiple_redemptions_accumulate_correctly(): void
    {
        $this->giveCoins($this->user, 200);

        CoinsService::applyRedemption($this->user, 50, 'membership', 1);
        CoinsService::applyRedemption($this->user, 30, 'souq', 2);

        $this->assertSame(120, CoinsService::getBalance($this->user));

        $membershipEntry = JannahCoinsLedger::where('user_id', $this->user->id)
            ->where('reason', 'redemption_membership')->first();
        $souqEntry = JannahCoinsLedger::where('user_id', $this->user->id)
            ->where('reason', 'redemption_souq')->first();

        $this->assertNotNull($membershipEntry);
        $this->assertNotNull($souqEntry);
    }
}
