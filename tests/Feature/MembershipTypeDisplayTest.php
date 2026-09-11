<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Livewire\Profile\ProfileScreen;
use App\Models\MemberProfile;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MembershipTypeDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);
    }

    // ─── Label helper ──────────────────────────────────────────────

    public function test_membership_type_label_maps_codes(): void
    {
        $this->assertSame('Member', MemberProfile::membershipTypeLabel('M'));
        $this->assertSame('SixteenMember', MemberProfile::membershipTypeLabel('SM'));
        $this->assertSame('Executive', MemberProfile::membershipTypeLabel('E'));
    }

    public function test_membership_type_label_falls_back_to_member(): void
    {
        $this->assertSame('Member', MemberProfile::membershipTypeLabel(null));
        $this->assertSame('Member', MemberProfile::membershipTypeLabel('X'));
        $this->assertSame('Member', MemberProfile::membershipTypeLabel(''));
    }

    // ─── Profile screen ─────────────────────────────────────────────

    #[DataProvider('membershipTypeProvider')]
    public function test_profile_header_shows_membership_type_badge(string $type, string $label): void
    {
        $user = $this->createMemberUser($type);

        $html = Livewire::actingAs($user)->test(ProfileScreen::class)->html();

        $this->assertStringContainsString('profile-type-badge', $html);
        $this->assertStringContainsString(sprintf('data-membership-type="%s"', $type), $html);
        $this->assertStringContainsString('profile-type-kicker', $html);
        $this->assertStringContainsString($label, $html);
    }

    #[DataProvider('membershipTypeProvider')]
    public function test_profile_membership_tab_shows_membership_type(string $type, string $label): void
    {
        $user = $this->createMemberUser($type);

        $component = Livewire::actingAs($user)->test(ProfileScreen::class);
        $component->set('tab', 'membership');

        $html = $component->html();
        $this->assertStringContainsString('Membership Type</p>', $html);
        $this->assertStringContainsString(sprintf('data-membership-type="%s"', $type), $html);
        $this->assertStringContainsString($label, $html);
    }

    public function test_profile_header_keeps_app_role_badge_next_to_type_badge(): void
    {
        $user = $this->createMemberUser('M');

        $html = Livewire::actingAs($user)->test(ProfileScreen::class)->html();

        $this->assertStringContainsString('profile-badge', $html);
        $this->assertStringContainsString('profile-type-badge', $html);
    }

    // ─── Legacy card ────────────────────────────────────────────────

    #[DataProvider('membershipTypeProvider')]
    public function test_legacy_card_shows_membership_type(string $type, string $label): void
    {
        $user = $this->createMemberUser($type);

        $this->actingAs($user)
            ->get('/profile/legacy-card')
            ->assertOk()
            ->assertSeeHtml(sprintf('data-membership-type="%s"', $type))
            ->assertSee($label)
            ->assertDontSee('TMC Member');
    }

    // ─── Approval email ─────────────────────────────────────────────

    public function test_approval_email_renders_mapped_membership_type(): void
    {
        $user = $this->createMemberUser('SM');

        $html = app(Markdown::class)->render('emails.membership.approved', [
            'user' => $user,
            'membershipId' => 'TMC-0001',
            'membershipType' => 'SM',
            'legacyCardUrl' => route('profile.legacy-card'),
            'paymentUrl' => route('membership.payment'),
        ]);

        $this->assertStringContainsString('SixteenMember', $html);
        $this->assertStringNotContainsString('Type:</strong> SM', $html);
    }

    // ─── Admin dropdown ─────────────────────────────────────────────

    public function test_admin_dropdown_shows_helper_labels(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('super_admin');
        $target = $this->createMemberUser('M');

        $component = Livewire::actingAs($admin)
            ->test(ViewUser::class, ['record' => $target->getKey()]);

        $component->mountAction('changeMembershipType');
        $html = $component->html();

        $this->assertStringContainsString('SixteenMember', $html);
        $this->assertStringContainsString('Executive', $html);
        $this->assertStringNotContainsString('SixtenMember', $html);
        $this->assertStringNotContainsString('Member (M)', $html);
    }

    public function test_admin_dropdown_still_submits_raw_codes(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('super_admin');
        $target = $this->createMemberUser('M');

        Livewire::actingAs($admin)
            ->test(ViewUser::class, ['record' => $target->getKey()])
            ->callAction('changeMembershipType', ['new_type' => 'E']);

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $target->getKey(),
            'membership_type' => 'E',
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public static function membershipTypeProvider(): array
    {
        return [
            'member' => ['M', 'Member'],
            'sixteen member' => ['SM', 'SixteenMember'],
            'executive' => ['E', 'Executive'],
        ];
    }

    protected function createMemberUser(string $membershipType = 'M'): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'membership_type' => $membershipType,
                'onboarding_status' => 'member',
            ],
        );

        return $user->fresh();
    }
}
