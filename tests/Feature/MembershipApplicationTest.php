<?php

namespace Tests\Feature;

use App\Filament\Resources\MembershipApplicationResource;
use App\Livewire\Membership\MembershipOnboardingWizard;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\MembershipApplicationDraft;
use App\Models\User;
use App\Notifications\MembershipApplicationSubmitted;
use App\Services\MembershipIdService;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
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
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'outside_nigeria_location' => '',
            'age_group' => '25_34',
            'marital_status' => 'married',
            'phone' => '+2348000000000',
            'selected_interests' => Interest::query()->limit(2)->pluck('slug')->all(),
            'selected_goals' => Goal::query()->limit(2)->pluck('slug')->all(),
            'instagram_username' => 'aisha_m',
            'facebook_username' => '',
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
            ->set('country', $data['country'])
            ->set('state', $data['state'])
            ->set('ageGroup', $data['age_group'])
            ->set('maritalStatus', $data['marital_status'])
            ->set('phone', $data['phone'])
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('selectedInterests', $data['selected_interests'])
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('selectedGoals', $data['selected_goals'])
            ->call('nextStep')
            ->assertSet('step', 5)
            ->set('instagramUsername', $data['instagram_username'])
            ->set('xUsername', $data['x_username'])
            ->call('nextStep')
            ->assertSet('step', 6)
            ->call('submit')
            ->assertRedirect(route('membership.pending'));
    }

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
            ->set('ageGroup', '25_34')
            ->set('maritalStatus', 'married')
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('country', 'Nigeria')
            ->set('state', 'Lagos')
            ->set('phone', '+2348000000000')
            ->call('nextStep')
            ->assertSet('step', 3);

        $this->assertDatabaseHas('membership_application_drafts', [
            'user_id' => $user->id,
            'current_step' => 2,
        ]);

        $draft = MembershipApplicationDraft::query()->where('user_id', $user->id)->first();
        $this->assertNull($draft->submitted_at);
        $this->assertEquals('Aisha', $draft->data['first_name']);
        $this->assertEquals('Lagos', $draft->data['state']);

        Livewire::actingAs($user)
            ->test(MembershipOnboardingWizard::class)
            ->assertSet('step', 2)
            ->assertSet('firstName', 'Aisha')
            ->assertSet('state', 'Lagos');
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
        $this->assertEquals('approved', $profile->onboarding_status);
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
            ->set('country', $data['country'])
            ->set('state', $data['state'])
            ->set('ageGroup', $data['age_group'])
            ->set('maritalStatus', $data['marital_status'])
            ->set('phone', $data['phone'])
            ->set('selectedInterests', $data['selected_interests'])
            ->set('selectedGoals', $data['selected_goals'])
            ->set('instagramUsername', $data['instagram_username'])
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
}
