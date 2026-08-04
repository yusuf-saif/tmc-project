<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesHibp;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use FakesHibp;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->fakeHibpWithNoBreach();
    }

    public function test_forgot_password_page_loads(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('Email Address');
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
        ]);

        $this->post('/forgot-password', [
            'email' => 'aisha@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            SetPasswordNotification::class,
            function (object $notification, array $channels) {
                return ! empty($notification->token);
            }
        );
    }

    public function test_reset_password_page_loads_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('New Password')
            ->assertSee('Confirm Password');
    }

    public function test_password_can_be_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Tmc2024!Sec#Pass99'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertTrue(Hash::check('Tmc2024!Sec#Pass99', $user->password));
    }

    public function test_old_password_no_longer_works_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Old!2024Secure#1'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Old!2024Secure#1',
        ])->assertSessionHasErrors('email');
    }

    public function test_new_password_works_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Tmc2024!Sec#Pass99'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
        ])->assertSessionHasNoErrors();
    }

    public function test_invalid_email_shows_error(): void
    {
        $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_expired_token_shows_error(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now()->subHours(2),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertSessionHasErrors('email');
    }

    public function test_password_confirmation_mismatch_shows_error(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('password');
    }

    public function test_forgot_password_notification_is_queued(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'status' => 'active',
        ]);

        $this->post('/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertSessionHas('status');

        Notification::assertSentTo(
            $user,
            SetPasswordNotification::class,
        );
    }

    public function test_breached_password_is_rejected_during_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Tmc2024!Sec#Pass99'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $password = 'Tmc2024!Sec#Pass99';
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);

        $this->resetHibpFakes();

        Http::fake([
            "api.pwnedpasswords.com/range/$prefix" => Http::response($suffix.':100000', 200),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertSessionHasErrors('password');
    }

    public function test_password_reset_succeeds_when_hibp_is_unavailable(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Tmc2024!Sec#Pass99'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->resetHibpFakes();

        Http::fake([
            'api.pwnedpasswords.com/*' => Http::response('Service unavailable', 503),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertTrue(Hash::check('Tmc2024!Sec#Pass99', $user->password));
    }

    public function test_password_reset_succeeds_when_hibp_request_throws_and_logs_warning(): void
    {
        Log::spy();

        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('Tmc2024!Sec#Pass99'),
        ]);

        $token = \Str::random(60);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->resetHibpFakes();

        Http::fake([
            'api.pwnedpasswords.com/*' => fn () => throw new ConnectionException('Connection timed out'),
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Tmc2024!Sec#Pass99',
            'password_confirmation' => 'Tmc2024!Sec#Pass99',
        ])->assertRedirect('/login');

        Log::shouldHaveReceived('warning')->once();

        $user->refresh();
        $this->assertTrue(Hash::check('Tmc2024!Sec#Pass99', $user->password));
    }
}
