<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\Interest;
use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use App\Services\MembersCsvImportService;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\FakesHibp;
use Tests\TestCase;

class CsvImportOnboardingTest extends TestCase
{
    use FakesHibp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeHibpWithNoBreach();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
        ]);
    }

    public function test_successful_import_creates_pending_users(): void
    {
        Storage::fake('public');

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            "TMC-M-1448-001,15 Muharram 1448,Fatima Labaran Aliyu,Fati,fatima@example.com\n".
            'TMC-M-1448-002,15 Muharram 1448,Aisha Bello,Aisha,aisha@example.com';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        $result = app(MembersCsvImportService::class)->import($path, 'public');

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        $this->assertDatabaseHas('users', [
            'email' => 'fatima@example.com',
            'name' => 'Fatima Labaran Aliyu',
            'member_id' => 'TMC-M-1448-001',
            'status' => 'pending_onboarding',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'aisha@example.com',
            'member_id' => 'TMC-M-1448-002',
        ]);

        $fatima = User::where('email', 'fatima@example.com')->first();
        $this->assertTrue($fatima->hasRole('member'));

        $profile = $fatima->memberProfile;
        $this->assertEquals('TMC-M-1448-001', $profile->membership_id);
        $this->assertEquals('M', $profile->membership_type);
        $this->assertEquals('Fati', $profile->display_name);
        $this->assertEquals('free', $profile->payment_status);
        $this->assertEquals('pending_onboarding', $profile->onboarding_status);
        $this->assertNotNull($profile->hijri_join_date);
    }

    public function test_membership_type_parsed_from_membership_id(): void
    {
        Storage::fake('public');

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            "TMC-SM-1448-001,1 Ramadan 1448,Sister Young,Sister,young@example.com\n".
            'TMC-E-1448-001,1 Shawwal 1448,Executive Member,Exec,exec@example.com';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        app(MembersCsvImportService::class)->import($path, 'public');

        $this->assertDatabaseHas('member_profiles', [
            'membership_id' => 'TMC-SM-1448-001',
            'membership_type' => 'SM',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'membership_id' => 'TMC-E-1448-001',
            'membership_type' => 'E',
        ]);
    }

    public function test_duplicate_email_is_skipped(): void
    {
        Storage::fake('public');

        User::factory()->create(['email' => 'existing@example.com']);

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            'TMC-M-1448-001,15 Muharram 1448,New Member,New,existing@example.com';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        $result = app(MembersCsvImportService::class)->import($path, 'public');

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertContains('existing@example.com', $result['skipped_emails']);
    }

    public function test_duplicate_membership_id_is_skipped(): void
    {
        Storage::fake('public');

        User::factory()->create(['member_id' => 'TMC-M-1448-001']);

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            'TMC-M-1448-001,15 Muharram 1448,Another Member,Another,another@example.com';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        $result = app(MembersCsvImportService::class)->import($path, 'public');

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    public function test_onboarding_invitation_email_is_sent(): void
    {
        Notification::fake();
        Storage::fake('public');

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            'TMC-M-1448-001,15 Muharram 1448,Fatima Labaran Aliyu,Fati,fatima@example.com';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        app(MembersCsvImportService::class)->import($path, 'public');

        $user = User::where('email', 'fatima@example.com')->first();

        Notification::assertSentTo(
            $user,
            OnboardingInvitationNotification::class,
            function (OnboardingInvitationNotification $notification) {
                return $notification->membershipId === 'TMC-M-1448-001';
            }
        );
    }

    public function test_valid_token_shows_onboarding_form(): void
    {
        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'status' => 'pending_onboarding',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get(route('onboarding.form', [
            'token' => $token,
            'email' => $user->email,
            'member_id' => $user->member_id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Complete your account');
        $response->assertSee($user->name);
        $response->assertSee($user->member_id);
    }

    public function test_invalid_token_redirects_with_error(): void
    {
        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'status' => 'pending_onboarding',
        ]);

        $response = $this->get(route('onboarding.form', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'member_id' => $user->member_id,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_expired_token_redirects_with_error(): void
    {
        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'status' => 'pending_onboarding',
        ]);

        $token = Password::broker()->createToken($user);

        Password::broker()->deleteToken($user);

        $response = $this->get(route('onboarding.form', [
            'token' => $token,
            'email' => $user->email,
            'member_id' => $user->member_id,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_already_active_user_redirected_from_onboarding(): void
    {
        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'status' => 'active',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get(route('onboarding.form', [
            'token' => $token,
            'email' => $user->email,
            'member_id' => $user->member_id,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_onboarding_completion_activates_account(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'name' => 'Fatima Labaran Aliyu',
            'status' => 'pending_onboarding',
            'email_verified_at' => null,
        ]);

        $user->assignRole('member');

        $user->memberProfile()->create([
            'membership_id' => 'TMC-M-1448-001',
            'membership_type' => 'M',
            'display_name' => 'Fati',
            'payment_status' => 'free',
            'onboarding_status' => 'pending_onboarding',
        ]);

        $interestSlugs = Interest::query()->where('is_active', true)->orderBy('sort_order')->limit(2)->pluck('slug')->all();
        $goalSlugs = Goal::query()->orderBy('id')->limit(2)->pluck('slug')->all();

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('onboarding.complete'), [
            'token' => $token,
            'email' => $user->email,
            'member_id' => $user->member_id,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
            'location_country' => 'Nigeria',
            'location_state' => 'Lagos',
            'age_group' => '25_34',
            'marital_status' => 'married',
            'phone' => '+2348012345678',
            'ig_username' => '@fati_ig',
            'fb_username' => 'fati.fb',
            'x_username' => '@fati_x',
            'tiktok_username' => '@fati_tiktok',
            'interests' => $interestSlugs,
            'goals' => $goalSlugs,
        ]);

        $response->assertRedirect(route('home'));

        $user->refresh();

        $this->assertEquals('active', $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Tmc2024!Sec#Pass99', $user->password));

        $profile = $user->memberProfile->refresh();
        $this->assertEquals('active', $profile->onboarding_status);
        $this->assertEquals('Nigeria', $profile->location_country);
        $this->assertEquals('Lagos', $profile->location_state);
        $this->assertEquals('25_34', $profile->age_group);
        $this->assertEquals('married', $profile->marital_status);
        $this->assertEquals('+2348012345678', $profile->phone);
        $this->assertEquals('@fati_ig', $profile->ig_username);
        $this->assertEquals('fati.fb', $profile->fb_username);
        $this->assertEquals('@fati_x', $profile->x_username);
        $this->assertEquals('@fati_tiktok', $profile->tiktok_username);
        $this->assertNotNull($profile->onboarding_completed_at);

        $this->assertCount(2, $user->interests);
        $this->assertCount(2, $user->goals);

        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_required_fields_are_reported(): void
    {
        Storage::fake('public');

        $csv = "MEMBERSHIP_ID,HIJRI_DATE,NAME,NICKNAME,EMAIL\n".
            ",,Some Name,Nick,somename@example.com\n".
            "TMC-M-1448-001,,,Nick,noname@example.com\n".
            'TMC-M-1448-002,15 Muharram 1448,Valid Name,Nick,';

        $path = 'imports/test.csv';
        Storage::disk('public')->put($path, $csv);

        $result = app(MembersCsvImportService::class)->import($path, 'public');

        $this->assertEquals(0, $result['imported']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_onboarding_form_validates_required_fields(): void
    {
        $user = User::factory()->create([
            'member_id' => 'TMC-M-1448-001',
            'status' => 'pending_onboarding',
        ]);

        $user->assignRole('member');

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('onboarding.complete'), [
            'token' => $token,
            'email' => $user->email,
            'member_id' => $user->member_id,
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'interests' => [],
            'goals' => [],
        ]);

        $response->assertSessionHasErrors([
            'password',
            'location_country',
            'age_group',
            'marital_status',
            'phone',
            'interests',
            'goals',
        ]);
    }
}
