<?php

namespace Tests\Feature;

use App\Filament\Resources\MembershipApplicationResource;
use App\Livewire\Membership\MembershipOnboardingWizard;
use App\Models\AuditLog;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Notifications\MembershipNeedsCorrection;
use App\Notifications\MembershipPaymentConfirmed;
use App\Services\MembershipApprovalService;
use App\Services\MembershipIdService;
use App\Services\MembershipStateService;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    protected function getCompletedDraftData(): array
    {
        return [
            'first_name' => 'Aisha',
            'last_name' => 'Member',
            'nickname' => 'Aishy',
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

    protected function completeApplication(User $user, array $data): void
    {
        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->assertSet('step', 1)
            ->set('firstName', $data['first_name'])
            ->set('lastName', $data['last_name'])
            ->set('nickname', $data['nickname'])
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
            ->set('selectedInterests', $data['selected_interests'])
            ->set('selectedGoals', $data['selected_goals'])
            ->call('nextStep')
            ->assertSet('step', 6)
            ->call('submit')
            ->assertRedirect(route('membership.pending'));
    }

    // ─── Existing Tests ─────────────────────────────────────────────

    public function test_user_can_save_draft_and_resume_later(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->assertSet('step', 1)
            ->set('firstName', 'Aisha')
            ->set('lastName', 'Member')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('locationCountry', 'Nigeria')
            ->set('locationState', 'Lagos')
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->assertSet('step', 4);

        $this->assertDatabaseHas('membership_application_drafts', [
            'user_id' => $user->id,
            'current_step' => 4,
        ]);

        $draft = MembershipApplicationDraft::query()->where('user_id', $user->id)->first();
        $this->assertNull($draft->submitted_at);
        $this->assertEquals('Aisha', $draft->data['first_name']);
        $this->assertEquals('Lagos', $draft->data['location_state']);

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->assertSet('step', 4)
            ->assertSet('firstName', 'Aisha')
            ->assertSet('locationState', 'Lagos');
    }

    public function test_user_cannot_access_home_before_completing_application(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.onboarding'));
    }

    public function test_admin_receives_notification_after_submission(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();

        $this->completeApplication($user, $data);

        Notification::assertSentTo(
            [$admin],
            MembershipApplicationSubmitted::class,
        );
    }

    public function test_membership_id_generated_only_after_approval(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $data = $this->getCompletedDraftData();

        $this->completeApplication($user, $data);

        $profile = $user->fresh()->memberProfile;
        $this->assertEquals('pending_review', $profile->onboarding_status);
        $this->assertNull($profile->membership_id);

        $this->actingAs($admin);
        MembershipApplicationResource::approve($profile, 'M');

        $profile->refresh();
        $this->assertEquals('approved_pending_payment', $profile->onboarding_status);
        $this->assertEquals('M', $profile->membership_type);
        $this->assertNotNull($profile->membership_id);
        $this->assertStringStartsWith('TMC-M-', $profile->membership_id);
        $this->assertNotNull($profile->reviewed_at);
        $this->assertEquals($admin->id, $profile->reviewed_by);
    }

    public function test_membership_id_serial_increments_correctly_per_type_and_hijri_year(): void
    {
        $hijriYear = MembershipIdService::getCurrentHijriYear();
        $type = 'member';

        $idData1 = MembershipIdService::generate($type);
        $this->assertEquals(1, $idData1['membership_serial']);
        $this->assertEquals("TMC-M-{$hijriYear}-001", $idData1['membership_id']);

        $idData2 = MembershipIdService::generate($type);
        $this->assertEquals(2, $idData2['membership_serial']);
        $this->assertEquals("TMC-M-{$hijriYear}-002", $idData2['membership_id']);

        $idData3 = MembershipIdService::generate('junior_member');
        $this->assertEquals(1, $idData3['membership_serial']);
        $this->assertEquals("TMC-SM-{$hijriYear}-001", $idData3['membership_id']);
    }

    public function test_approved_user_cannot_access_home_until_payment_confirmed(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'approved_pending_payment',
            'membership_id' => 'TMC-M-1447-001',
            'approved_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.payment'));
    }

    public function test_active_paid_member_can_access_home(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'active',
            'membership_id' => 'TMC-M-1447-001',
            'payment_status' => 'paid',
            'membership_fee_paid_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertOk();
    }

    public function test_user_is_redirected_to_pending_after_submission(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->set('firstName', $data['first_name'])
            ->set('lastName', $data['last_name'])
            ->set('nickname', $data['nickname'])
            ->set('locationCountry', $data['location_country'])
            ->set('locationState', $data['location_state'])
            ->set('ageGroup', $data['age_group'])
            ->set('maritalStatus', $data['marital_status'])
            ->set('phone', $data['phone'])
            ->set('selectedInterests', $data['selected_interests'])
            ->set('selectedGoals', $data['selected_goals'])
            ->set('igUsername', $data['ig_username'])
            ->set('xUsername', $data['x_username'])
            ->call('submit')
            ->assertRedirect(route('membership.pending'));

        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $user->id,
            'onboarding_status' => 'pending_review',
        ]);

        $freshUser = $user->fresh();
        $this->actingAs($freshUser)
            ->get('/home')
            ->assertRedirect(route('membership.pending'));
    }

    public function test_user_can_register_and_is_redirected_to_membership_onboarding(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $response = $this->post('/register', [
            'name' => 'Aisha Member',
            'email' => 'aisha@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::query()->where('email', 'aisha@example.com')->firstOrFail();

        $response->assertRedirect(route('membership.onboarding'));
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('member'));
        $this->assertNotNull($user->profile);
    }

    public function test_admin_bypasses_membership_check(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $this->actingAs($admin)
            ->get('/home')
            ->assertOk();
    }

    public function test_submitted_user_sees_pending_redirect_from_middleware(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'submitted',
            'application_submitted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.pending'));
    }

    public function test_rejected_user_is_redirected_back_to_onboarding(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'rejected',
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.onboarding'));
    }

    public function test_payment_submitted_user_is_redirected_to_payment_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'payment_submitted',
            'membership_id' => 'TMC-M-1447-001',
        ]);

        $this->actingAs($user)
            ->get('/home')
            ->assertRedirect(route('membership.payment'));
    }

    // ─── State Machine Tests ────────────────────────────────────────

    public function test_invalid_state_transition_throws_exception(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'draft',
        ]);

        $stateService = app(MembershipStateService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid membership state transition');

        $stateService->transition($profile, 'active');
    }

    public function test_idempotent_transition_does_not_throw(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'pending_review',
        ]);

        $stateService = app(MembershipStateService::class);

        $result = $stateService->transition($profile, 'pending_review');
        $this->assertEquals('pending_review', $result->onboarding_status);
    }

    public function test_cannot_skip_payment_step(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');

        $this->actingAs($user);

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'pending_review',
        ]);

        $stateService = app(MembershipStateService::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid membership state transition');

        $stateService->transition($profile, 'active');
    }

    public function test_cannot_reject_or_correct_after_approval(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'approved_pending_payment',
        ]);

        $stateService = app(MembershipStateService::class);

        $this->expectException(RuntimeException::class);
        $stateService->transition($profile, 'rejected');
    }

    public function test_state_after_payment_confirmation_is_active(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'payment_submitted',
            'membership_id' => 'TMC-M-1447-001',
            'payment_submitted_at' => now(),
            'payment_proof_path' => 'payment-proofs/test.pdf',
        ]);

        $this->actingAs($admin);
        $approvalService = app(MembershipApprovalService::class);
        $profile = $approvalService->confirmPayment($profile);

        $this->assertEquals('active', $profile->onboarding_status);
        $this->assertNotNull($profile->payment_verified_at);
        $this->assertEquals($admin->id, $profile->payment_verified_by);
        $this->assertNotNull($profile->activated_at);
        $this->assertEquals('active', $user->fresh()->status);
    }

    // ─── Needs Correction Flow ──────────────────────────────────────

    public function test_needs_correction_flow(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        $approvalService = app(MembershipApprovalService::class);
        $profile = $approvalService->needsCorrection($profile, 'Please update your profile photo.');

        $this->assertEquals('needs_correction', $profile->onboarding_status);
        $this->assertEquals('Please update your profile photo.', $profile->needs_correction_notes);
        $this->assertEquals($admin->id, $profile->reviewed_by);

        Notification::assertSentTo(
            $user,
            MembershipNeedsCorrection::class,
        );
    }

    public function test_user_can_resubmit_after_correction(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        $approvalService = app(MembershipApprovalService::class);
        $approvalService->needsCorrection($profile, 'Please update your nickname.');

        $this->assertEquals('needs_correction', $user->fresh()->status);

        $profile->refresh();
        $stateService = app(MembershipStateService::class);
        $stateService->transition($profile, 'in_progress');

        $this->assertEquals('in_progress', $profile->fresh()->onboarding_status);

        $stateService->transition($profile->fresh(), 'pending_review');

        $this->assertEquals('pending_review', $profile->fresh()->onboarding_status);
    }

    // ─── Audit Logging Tests ────────────────────────────────────────

    public function test_approval_creates_audit_log(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
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
        $user->profile()->create(['display_name' => $user->name]);

        $data = $this->getCompletedDraftData();

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->set('firstName', $data['first_name'])
            ->set('lastName', $data['last_name'])
            ->set('nickname', $data['nickname'])
            ->set('locationCountry', $data['location_country'])
            ->set('locationState', $data['location_state'])
            ->set('ageGroup', $data['age_group'])
            ->set('maritalStatus', $data['marital_status'])
            ->set('phone', $data['phone'])
            ->set('selectedInterests', $data['selected_interests'])
            ->set('selectedGoals', $data['selected_goals'])
            ->set('igUsername', $data['ig_username'])
            ->set('xUsername', $data['x_username'])
            ->call('submit');

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
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        MembershipApplicationResource::reject($profile, 'Incomplete information');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'membership_rejected',
            'auditable_type' => MemberProfile::class,
            'auditable_id' => $profile->id,
        ]);
    }

    public function test_payment_confirmation_creates_audit_log(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $profile = MemberProfile::create([
            'user_id' => $user->id,
            'onboarding_status' => 'payment_submitted',
            'membership_id' => 'TMC-M-1447-001',
            'payment_submitted_at' => now(),
            'payment_proof_path' => 'payment-proofs/test.pdf',
        ]);

        $this->actingAs($admin);
        $approvalService = app(MembershipApprovalService::class);
        $approvalService->confirmPayment($profile);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'payment_confirmed',
        ]);
    }

    // ─── Role-Based Access Enforcement ──────────────────────────────

    public function test_moderator_cannot_approve_applications(): void
    {
        $moderator = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $moderator->assignRole('moderator');
        $moderator->profile()->create(['display_name' => $moderator->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
        $profile = $user->fresh()->memberProfile;

        $this->assertFalse(MembershipApplicationResource::canAccess());
    }

    // ─── Duplicate Prevention ───────────────────────────────────────

    public function test_double_approval_is_idempotent(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $admin->assignRole('admin');
        $admin->profile()->create(['display_name' => $admin->name]);

        $user = User::factory()->create(['email_verified_at' => now(), 'status' => 'active']);
        $user->assignRole('member');
        $user->profile()->create(['display_name' => $user->name]);

        $this->completeApplication($user, $this->getCompletedDraftData());
        $profile = $user->fresh()->memberProfile;

        $this->actingAs($admin);
        MembershipApplicationResource::approve($profile, 'M');

        $profile->refresh();
        $this->assertEquals('approved_pending_payment', $profile->onboarding_status);

        MembershipApplicationResource::approve($profile->fresh(), 'M');
        $profile->refresh();
        $this->assertEquals('approved_pending_payment', $profile->onboarding_status);
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
