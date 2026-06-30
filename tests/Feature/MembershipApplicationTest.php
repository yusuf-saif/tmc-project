<?php

namespace Tests\Feature;

use App\Events\MembershipSubmitted;
use App\Filament\Resources\MembershipApplicationResource;
use App\Livewire\Membership\MembershipSignupWizard;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Notifications\MembershipNeedsCorrection;
use App\Services\MembershipApprovalService;
use App\Services\MembershipIdService;
use App\Services\MembershipStateService;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class MembershipApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
        ]);
    }

    protected function completeApplication(User $user, array $data): void
    {
        Livewire::actingAs($user)
            ->test(MembershipSignupWizard::class)
            ->assertSet('step', 1)
            ->set('firstName', $data['first_name'])
            ->set('lastName', $data['last_name'])
            ->set('email', $data['email'] ?? ($user->email))
            ->set('password', 'Password123!')
            ->set('passwordConfirmation', 'Password123!')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('locationCountry', $data['location_country'])
            ->set('locationState', $data['location_state'])
            ->set('ageGroup', $data['age_group'])
            ->set('maritalStatus', $data['marital_status'])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('phone', $data['phone'])
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('igUsername', $data['ig_username'])
            ->set('xUsername', $data['x_username'])
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('preferredBillingCycle', 'monthly')
            ->call('nextStep')
            ->assertSet('step', 6)
            ->call('toggleInterest', Interest::query()->limit(1)->first()->slug)
            ->call('toggleGoal', Goal::query()->limit(1)->first()->slug)
            ->call('submit')
            ->assertRedirect(route('home'));
    }

    protected function getCompletedDraftData(): array
    {
        return [
            'first_name' => 'Aisha',
            'last_name' => 'Member',
            'location_country' => 'Nigeria',
            'location_state' => 'Lagos',
            'location_international' => '',
            'age_group' => '25_34',
            'marital_status' => 'married',
            'phone' => '+2348000000000',
            'selected_interests' => Interest::query()->limit(2)->pluck('slug')->all(),
            'selected_goals' => Goal::query()->limit(2)->pluck('slug')->all(),
            'ig_username' => 'aisha_m',
            'fb_username' => '',
            'x_username' => 'aisha_x',
            'tiktok_username' => '',
        ];
    }

    protected function createSubmittedProfile(User $user, array $data): MemberProfile
    {
        $interestSlugs = $data['selected_interests'] ?? [];
        $goalSlugs = $data['selected_goals'] ?? [];

        $profile = MemberProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'location_country' => $data['location_country'],
                'location_state' => $data['location_state'],
                'age_group' => $data['age_group'],
                'marital_status' => $data['marital_status'],
                'phone' => $data['phone'],
                'ig_username' => $data['ig_username'],
                'fb_username' => $data['fb_username'],
                'x_username' => $data['x_username'],
                'tiktok_username' => $data['tiktok_username'],
                'onboarding_status' => 'onboarding',
                'submitted_at' => now(),
            ],
        );

        if ($interestSlugs !== []) {
            $interestIds = Interest::whereIn('slug', $interestSlugs)->pluck('id')->all();
            $user->interests()->sync($interestIds);
        }

        if ($goalSlugs !== []) {
            $goalIds = Goal::whereIn('slug', $goalSlugs)->pluck('id')->all();
            $user->goals()->sync($goalIds);
        }

        return $profile;
    }

    // ─── Existing Tests ─────────────────────────────────────────────

    public function test_user_cannot_access_home_before_completing_application(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.signup'));
    }

    public function test_admin_receives_notification_after_submission(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        MembershipSubmitted::dispatch($user->fresh()->memberProfile, $user);

        Notification::assertSentTo(
            [$admin],
            MembershipApplicationSubmitted::class,
        );
    }

    public function test_membership_id_generated_only_after_approval(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);

        $profile = $user->fresh()->memberProfile;
        $this->assertEquals('onboarding', $profile->onboarding_status);
        $this->assertNull($profile->membership_id);

        $this->actingAs($admin);
        MembershipApplicationResource::approve($profile, 'M');

        $profile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);
        $this->assertEquals('M', $profile->membership_type);
        $this->assertNotNull($profile->membership_id);
        $this->assertStringStartsWith('TMC-M-', $profile->membership_id);
        $this->assertNotNull($profile->reviewed_at);
        $this->assertEquals($admin->id, $profile->reviewed_by);
    }

    public function test_membership_id_serial_increments_correctly_per_type_and_hijri_year(): void
    {
        $hijriYear = MembershipIdService::getCurrentHijriYear();

        $idData1 = MembershipIdService::generate('member');
        $this->assertEquals(1, $idData1['membership_serial']);
        $this->assertEquals("TMC-M-{$hijriYear}-001", $idData1['membership_id']);

        $idData2 = MembershipIdService::generate('member');
        $this->assertEquals(2, $idData2['membership_serial']);
        $this->assertEquals("TMC-M-{$hijriYear}-002", $idData2['membership_id']);

        $idData3 = MembershipIdService::generate('junior_member');
        $this->assertEquals(1, $idData3['membership_serial']);
        $this->assertEquals("TMC-SM-{$hijriYear}-001", $idData3['membership_id']);
    }

    public function test_onboarding_user_is_redirected_to_signup(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'onboarding',
            ],
        );

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.signup'));
    }

    public function test_active_paid_member_can_access_home(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'active',
                'membership_id' => 'TMC-M-1447-001',
                'payment_status' => 'paid',
                'activated_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_active_user_can_access_home(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $user->name,
                'onboarding_status' => 'active',
                'membership_id' => 'TMC-M-1447-001',
                'activated_at' => now(),
            ],
        );

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_admin_bypasses_membership_check(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $this->actingAs($admin)
            ->get('/home')
            ->assertOk();
    }

    // ─── State Machine Tests ────────────────────────────────────────

    public function test_invalid_state_transition_throws_exception(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $profile = $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'registered'],
        );

        $stateService = app(MembershipStateService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid membership state transition');

        $stateService->transition($profile, 'active');
    }

    public function test_idempotent_transition_does_not_throw(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $profile = $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'onboarding'],
        );

        $stateService = app(MembershipStateService::class);

        $result = $stateService->transition($profile, 'onboarding');
        $this->assertEquals('onboarding', $result->onboarding_status);
    }

    public function test_can_transition_from_onboarding_to_active(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');

        $profile = $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'onboarding'],
        );

        $stateService = app(MembershipStateService::class);
        $result = $stateService->transition($profile, 'active');

        $this->assertEquals('active', $result->onboarding_status);
    }

    public function test_cannot_revert_from_active_to_onboarding(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');

        $profile = $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'active'],
        );

        $stateService = app(MembershipStateService::class);

        $this->expectException(RuntimeException::class);
        $stateService->transition($profile, 'onboarding');
    }

    // ─── Needs Correction Flow (admin role upgrades) ────────────────

    public function test_needs_correction_flow(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        $approvalService = app(MembershipApprovalService::class);
        $profile = $approvalService->needsCorrection($profile, 'Please update your profile photo.');

        $this->assertEquals('Please update your profile photo.', $profile->needs_correction_notes);
        $this->assertEquals($admin->id, $profile->reviewed_by);

        Notification::assertSentTo(
            $user,
            MembershipNeedsCorrection::class,
        );
    }

    // ─── Audit Logging Tests ────────────────────────────────────────

    public function test_approval_creates_audit_log(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        MembershipApplicationResource::approve($profile, 'M');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership_id_generated',
            'auditable_type' => MemberProfile::class,
            'auditable_id' => $profile->id,
        ]);
    }

    public function test_membership_submission_creates_audit_log(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        MembershipSubmitted::dispatch($user->fresh()->memberProfile, $user);

        $profile = $user->fresh()->memberProfile;

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership_submitted',
            'auditable_type' => MemberProfile::class,
            'auditable_id' => $profile->id,
            'target_user_id' => $user->id,
        ]);
    }

    public function test_rejection_creates_audit_log(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        MembershipApplicationResource::reject($profile, 'Incomplete information');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership_rejected',
            'auditable_type' => MemberProfile::class,
            'auditable_id' => $profile->id,
        ]);
    }

    // ─── Role-Based Access Enforcement ──────────────────────────────

    public function test_moderator_cannot_approve_applications(): void
    {
        $moderator = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $moderator->assignRole('moderator');
        $moderator->memberProfile()->updateOrCreate(['display_name' => $moderator->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        $profile = $user->fresh()->memberProfile;

        $this->assertFalse(MembershipApplicationResource::canAccess());
    }

    // ─── Duplicate Prevention ───────────────────────────────────────

    public function test_double_approval_is_idempotent(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->memberProfile()->updateOrCreate(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();
        $this->createSubmittedProfile($user, $data);
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        MembershipApplicationResource::approve($profile, 'M');

        $profile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);

        MembershipApplicationResource::approve($profile->fresh(), 'M');
        $profile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);
    }

    // ─── Serial Number Integrity ────────────────────────────────────

    public function test_membership_id_serial_resets_per_hijri_year(): void
    {
        $serial1 = MembershipIdService::generate('member');
        $this->assertEquals(1, $serial1['membership_serial']);

        $serial2 = MembershipIdService::generate('member');
        $this->assertEquals(2, $serial2['membership_serial']);

        $serial3 = MembershipIdService::generate('student_member');
        $this->assertEquals(1, $serial3['membership_serial']);
    }
}
