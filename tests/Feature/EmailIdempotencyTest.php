<?php

namespace Tests\Feature;

use App\Events\MembershipActivated;
use App\Events\MembershipSubmitted;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\MembershipPaymentConfirmed as MembershipPaymentConfirmedNotification;
use App\Notifications\MembershipUnderReviewNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected MemberProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('member');

        $this->profile = MemberProfile::create([
            'user_id' => $this->user->id,
            'onboarding_status' => 'payment_processing',
            'membership_id' => 'TMC-M-1447-001',
            'preferred_billing_cycle' => 'monthly',
        ]);
    }

    public function test_payment_confirmed_email_is_sent_only_once(): void
    {
        Notification::fake();

        $this->profile->forceFill(['payment_confirmed_email_sent_at' => now()])->saveQuietly();

        $event = new MembershipActivated($this->user, 'TMC-M-1447-001', $this->user);
        Event::dispatch($event);

        Notification::assertNothingSent();
    }

    public function test_payment_confirmed_email_is_sent_when_not_sent_before(): void
    {
        Notification::fake();

        $this->profile->forceFill(['payment_confirmed_email_sent_at' => null])->saveQuietly();

        $event = new MembershipActivated($this->user, 'TMC-M-1447-001', $this->user);
        Event::dispatch($event);

        Notification::assertSentTo($this->user, MembershipPaymentConfirmedNotification::class);

        $this->profile->refresh();
        $this->assertNotNull($this->profile->payment_confirmed_email_sent_at);
    }

    public function test_under_review_email_is_sent_only_once(): void
    {
        Notification::fake();

        $this->profile->forceFill(['under_review_email_sent_at' => now()])->saveQuietly();

        $event = new MembershipSubmitted($this->profile, $this->user);
        Event::dispatch($event);

        Notification::assertNothingSent();
    }

    public function test_under_review_email_is_sent_when_not_sent_before(): void
    {
        Notification::fake();

        $this->profile->forceFill(['under_review_email_sent_at' => null])->saveQuietly();

        $event = new MembershipSubmitted($this->profile, $this->user);
        Event::dispatch($event);

        Notification::assertSentTo($this->user, MembershipUnderReviewNotification::class);

        $this->profile->refresh();
        $this->assertNotNull($this->profile->under_review_email_sent_at);
    }
}
