<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the PWA install card added to the home dashboard.
 *
 * What we implemented:
 *  1. `#home-install-card` Alpine component in home-dashboard.blade.php
 *     — hidden until JS evaluates install-ability (x-cloak / x-show)
 *     — calls installPWA() on Android, $dispatch('open-ios-install-instructions') on iOS
 *     — hides itself when the global 'appinstalled' event fires
 *  2. `resources/views/partials/ios-install-instructions.blade.php` extracted
 *  3. `#ios-install-banner` in app.blade.php updated to:
 *     — use @include('partials.ios-install-instructions') instead of inline markup
 *     — listen for @open-ios-install-instructions.window so the home card can trigger it
 *
 * Client-side Alpine visibility cannot be tested server-side.
 * These tests cover: markup presence, JS function definitions, Alpine wiring,
 * partial content, partial deduplication, and page access for different user states.
 */
class HomeInstallButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    // ── 1. Home install card markup ───────────────────────────────────────────

    public function test_home_install_card_element_is_present(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="home-install-card"', escape: false);
    }

    public function test_install_card_uses_x_cloak_for_flash_prevention(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // x-cloak hides the card until Alpine has evaluated installability —
        // prevents a flash of the card on non-installable browsers.
        $response->assertSee('x-cloak', escape: false);
    }

    public function test_install_card_uses_x_show_for_visibility(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('x-show="show"', escape: false);
    }

    public function test_install_card_has_install_button(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('handleClick()', escape: false);
        $response->assertSee('Install', escape: false);
    }

    public function test_install_card_dispatches_ios_event_on_ios_path(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // The handleClick() method dispatches this event on iOS devices.
        $response->assertSee('open-ios-install-instructions', escape: false);
    }

    public function test_install_card_calls_install_pwa_on_android_path(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // handleClick() calls installPWA() when isIos is false.
        $response->assertSee('installPWA()', escape: false);
    }

    public function test_install_card_listens_to_appinstalled_event_to_hide_itself(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // The Alpine init() hook registers a listener so the card disappears after install.
        $response->assertSee('appinstalled', escape: false);
    }

    public function test_install_card_re_evaluates_on_beforeinstallprompt(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // Chrome fires beforeinstallprompt asynchronously — the card must re-evaluate
        // after Alpine has already initialised.
        $response->assertSee('beforeinstallprompt', escape: false);
    }

    // ── 2. Global PWA JS functions are defined in the layout ─────────────────

    public function test_install_pwa_function_is_defined_in_layout_script(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('function installPWA()', escape: false);
    }

    public function test_is_ios_function_is_defined_in_layout_script(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('function isIOS()', escape: false);
    }

    public function test_is_already_standalone_function_is_defined_in_layout_script(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('function isAlreadyStandalone()', escape: false);
    }

    public function test_is_in_standalone_mode_function_is_defined_in_layout_script(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('function isInStandaloneMode()', escape: false);
    }

    public function test_deferred_prompt_variable_is_declared_in_layout_script(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('window.__tmcInstall', escape: false);
        $response->assertSee('data-navigate-once', escape: false);
    }

    // ── 3. iOS install instructions partial ──────────────────────────────────

    public function test_ios_install_instructions_partial_file_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/partials/ios-install-instructions.blade.php'),
            'The ios-install-instructions partial must exist at resources/views/partials/'
        );
    }

    public function test_ios_install_instructions_partial_contains_share_button_copy(): void
    {
        $partial = file_get_contents(resource_path('views/partials/ios-install-instructions.blade.php'));

        $this->assertStringContainsString('Share', $partial);
        $this->assertStringContainsString('Add to Home Screen', $partial);
    }

    public function test_ios_install_instructions_partial_contains_share_svg_icon(): void
    {
        $partial = file_get_contents(resource_path('views/partials/ios-install-instructions.blade.php'));

        $this->assertStringContainsString('<svg', $partial);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $partial);
    }

    public function test_ios_install_instructions_rendered_in_layout_banner(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // Partial content must appear — confirms @include resolved correctly.
        $response->assertSee('Add to Home Screen', escape: false);
        $response->assertSee('Share', escape: false);
    }

    // ── 4. iOS banner Alpine wiring ───────────────────────────────────────────

    public function test_ios_install_banner_element_is_present_in_layout(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('id="ios-install-banner"', escape: false);
    }

    public function test_ios_banner_listens_for_open_ios_install_instructions_window_event(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // The banner uses Alpine's @event.window shorthand so both the home card
        // and any other surface can trigger it via $dispatch.
        $response->assertSee('@open-ios-install-instructions.window', escape: false);
    }

    public function test_ios_banner_has_dismiss_button_with_local_storage_key(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        // Dismiss sets tmc_ios_install_dismissed so the visit-count logic respects it.
        $response->assertSee('tmc_ios_install_dismissed', escape: false);
    }

    // ── 5. Deduplication — partial content appears exactly once ─────────────

    public function test_add_to_home_screen_copy_appears_exactly_once(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $count = substr_count($response->getContent(), 'Add to Home Screen');

        $this->assertSame(1, $count, '"Add to Home Screen" should appear exactly once — the partial must not be duplicated.');
    }

    public function test_ios_install_banner_id_appears_exactly_once(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $count = substr_count($response->getContent(), 'id="ios-install-banner"');

        $this->assertSame(1, $count, '#ios-install-banner must not be duplicated in the DOM.');
    }

    // ── 6. x-cloak CSS rule is present ───────────────────────────────────────

    public function test_x_cloak_rule_is_defined_in_app_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('[x-cloak]', $css);
        $this->assertStringContainsString('display: none', $css);
    }

    // ── 7. Card is present regardless of onboarding_status variant ───────────

    public function test_install_card_present_for_fully_active_member_status(): void
    {
        $member = $this->createMemberWithStatus('member');

        $response = $this->actingAs($member)->get(route('home'));

        $response->assertOk();
        $response->assertSee('home-install-card', escape: false);
    }

    public function test_install_card_present_for_active_onboarding_status(): void
    {
        $member = $this->createMemberWithStatus('active');

        $response = $this->actingAs($member)->get(route('home'));

        $response->assertOk();
        $response->assertSee('home-install-card', escape: false);
    }

    // ── 8. Android install banner is still present (no regression) ───────────

    public function test_android_install_banner_still_present_in_layout(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('id="install-banner"', escape: false);
    }

    public function test_android_banner_not_now_button_sets_local_storage_key(): void
    {
        $response = $this->actingAs($this->createActiveMember())->get(route('home'));

        $response->assertSee('tmc_install_dismissed', escape: false);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createActiveMember(): User
    {
        return $this->createMemberWithStatus('member');
    }

    private function createMemberWithStatus(string $onboardingStatus): User
    {
        $user = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'INST'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);

        $user->assignRole('member');

        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => $onboardingStatus,
                'activated_at' => now(),
                'onboarding_completed_at' => now(),
                'current_period_ends_at' => now()->addDays(30),
            ],
        );

        return $user;
    }
}
