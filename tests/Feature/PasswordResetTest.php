<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->withoutMiddleware(ValidateCsrfToken::class);
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
            'password' => Hash::make('OldPassword123!'),
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
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect('/login');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
    }

    public function test_old_password_no_longer_works_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('OldPassword123!'),
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
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'OldPassword123!',
        ])->assertSessionHasErrors('email');
    }

    public function test_new_password_works_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'aisha@example.com',
            'status' => 'active',
            'password' => Hash::make('OldPassword123!'),
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
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ])->assertRedirect('/login');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
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
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
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
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ])->assertSessionHasErrors('password');
    }
}
