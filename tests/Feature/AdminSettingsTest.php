<?php

namespace Tests\Feature;

use App\Events\MembershipActivated;
use App\Filament\Pages\SettingsPage;
use App\Listeners\AwardWelcomeCoins;
use App\Models\JannahCoinsLedger;
use App\Models\Setting;
use App\Models\User;
use App\Services\CoinsService;
use App\Settings\SettingsRegistry;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'super@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'SUPER01',
        ]);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdmin->memberProfile()->updateOrCreate([
            'display_name' => $this->superAdmin->name,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_settings_registry_contains_expected_keys(): void
    {
        $keys = array_keys(SettingsRegistry::all());

        $this->assertContains('membership_fee_monthly', $keys);
        $this->assertContains('membership_billing_cycle_days', $keys);
        $this->assertContains('membership_grace_period_days', $keys);
        $this->assertContains('membership_reminder_days_before', $keys);
        $this->assertContains('souq_listing_fee_kobo', $keys);
        $this->assertContains('souq_billing_months', $keys);
        $this->assertContains('referral_coins_amount', $keys);
        $this->assertContains('starter_coins_amount', $keys);
        $this->assertContains('notify_renewal_reminders_enabled', $keys);
        $this->assertContains('notify_event_reminders_enabled', $keys);
        $this->assertContains('notify_souq_approval_enabled', $keys);
        $this->assertContains('bank_details', $keys);
        $this->assertContains('donate_message', $keys);
        $this->assertContains('suggested_donation_1', $keys);
        $this->assertContains('suggested_donation_2', $keys);
        $this->assertContains('suggested_donation_3', $keys);
        $this->assertContains('support_banner_text', $keys);
        $this->assertContains('event_reminder_hours_before', $keys);
    }

    public function test_setting_get_returns_registry_default_when_not_in_db(): void
    {
        $this->assertSame(5000, Setting::get('membership_fee_monthly'));
        $this->assertSame(30, Setting::get('membership_billing_cycle_days'));
        $this->assertSame(7, Setting::get('membership_grace_period_days'));
        $this->assertSame(500000, Setting::get('souq_listing_fee_kobo'));
        $this->assertSame(1, Setting::get('souq_billing_months'));
        $this->assertSame(24, Setting::get('event_reminder_hours_before'));
        $this->assertTrue(Setting::get('notify_renewal_reminders_enabled'));
        $this->assertSame('Support our sisterhood →', Setting::get('support_banner_text'));
    }

    public function test_setting_get_returns_db_value_when_set(): void
    {
        Setting::set('membership_billing_cycle_days', 45);

        $this->assertSame(45, Setting::get('membership_billing_cycle_days'));
    }

    public function test_setting_get_returns_overridden_default(): void
    {
        $this->assertSame(777, Setting::get('membership_billing_cycle_days', 777));
    }

    public function test_setting_set_throws_for_unknown_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Setting::set('nonexistent_key', 'value');
    }

    public function test_setting_getValue_still_works_for_backward_compat(): void
    {
        Setting::set('bank_details', 'Bank XYZ');

        $this->assertSame('Bank XYZ', Setting::getValue('bank_details'));
        $this->assertSame('fallback', Setting::getValue('nonexistent', 'fallback'));
    }

    public function test_settings_registry_has_all_groups(): void
    {
        $groups = SettingsRegistry::groups();

        $this->assertArrayHasKey('membership', $groups);
        $this->assertArrayHasKey('souq', $groups);
        $this->assertArrayHasKey('coins', $groups);
        $this->assertArrayHasKey('notifications', $groups);
        $this->assertArrayHasKey('donations', $groups);
        $this->assertArrayHasKey('content', $groups);
        $this->assertArrayHasKey('events', $groups);
    }

    public function test_super_admin_can_access_settings_page(): void
    {
        $this->actingAs($this->superAdmin)
            ->get('/admin/settings')
            ->assertOk();
    }

    public function test_admin_cannot_access_settings_page(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'ADMIN01',
        ]);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate([
            'display_name' => $admin->name,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_settings_page_can_save_values(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'bank_details' => 'GTBank 0123456789',
                'donate_message' => 'Support our cause',
                'membership_fee_monthly' => 7500,
                'membership_billing_cycle_days' => 45,
                'membership_grace_period_days' => 14,
                'membership_reminder_days_before' => 5,
                'souq_listing_fee_kobo' => 1000000,
                'souq_billing_months' => 3,
                'referral_coins_amount' => 50,
                'starter_coins_amount' => 100,
                'notify_renewal_reminders_enabled' => false,
                'notify_event_reminders_enabled' => false,
                'notify_souq_approval_enabled' => false,
                'support_banner_text' => 'Donate today →',
                'event_reminder_hours_before' => 48,
                'suggested_donation_1' => 2000,
                'suggested_donation_2' => 5000,
                'suggested_donation_3' => 10000,
            ])
            ->call('save');

        $this->assertSame('GTBank 0123456789', Setting::getValue('bank_details'));
        $this->assertSame(45, Setting::get('membership_billing_cycle_days'));
        $this->assertFalse(Setting::get('notify_renewal_reminders_enabled'));
        $this->assertSame(1000000, Setting::get('souq_listing_fee_kobo'));
        $this->assertSame(3, Setting::get('souq_billing_months'));
        $this->assertSame('Donate today →', Setting::get('support_banner_text'));
    }

    public function test_settings_page_can_save_single_field(): void
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(SettingsPage::class)
            ->fillForm(['bank_details' => 'Access Bank 9876543210'])
            ->call('save');

        $this->assertSame('Access Bank 9876543210', Setting::getValue('bank_details'));
    }

    public function test_award_welcome_coins_on_activation(): void
    {
        $user = User::factory()->create([
            'email' => 'newmember@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'WELC001',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate([
            'display_name' => $user->name,
            'onboarding_completed_at' => now(),
        ]);

        CoinsService::award($user, 10, 'manual'); // Pre-existing balance

        $listener = app(AwardWelcomeCoins::class);
        $listener->handle(new MembershipActivated($user, 'TMC-M-0001', $user));

        $this->assertSame(60, CoinsService::getBalance($user)); // 10 + 50 (default starter)
        $this->assertDatabaseHas('jannah_coins_ledger', [
            'user_id' => $user->id,
            'reason' => 'welcome',
            'amount' => 50,
        ]);
    }

    public function test_award_welcome_coins_skips_when_amount_is_zero(): void
    {
        Setting::set('starter_coins_amount', 0);

        $user = User::factory()->create([
            'email' => 'zero@example.com',
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => 'ZERO01',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate([
            'display_name' => $user->name,
            'onboarding_completed_at' => now(),
        ]);

        $listener = app(AwardWelcomeCoins::class);
        $listener->handle(new MembershipActivated($user, 'TMC-M-0002', $user));

        $this->assertSame(0, CoinsService::getBalance($user));
        $this->assertDatabaseMissing('jannah_coins_ledger', [
            'user_id' => $user->id,
            'reason' => 'welcome',
        ]);
    }

    public function test_registry_does_not_contain_mail_from_name_or_maintenance_mode(): void
    {
        $keys = array_keys(SettingsRegistry::all());

        $this->assertNotContains('mail_from_name', $keys);
        $this->assertNotContains('maintenance_mode', $keys);
    }

    public function test_souq_listing_fee_kobo_is_in_registry_with_correct_default(): void
    {
        $this->assertSame(500000, SettingsRegistry::default('souq_listing_fee_kobo'));
        $this->assertSame('souq', SettingsRegistry::group('souq_listing_fee_kobo'));
    }
}
