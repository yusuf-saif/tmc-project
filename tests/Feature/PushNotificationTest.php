<?php

namespace Tests\Feature;

use App\Events\MembershipActivated;
use App\Jobs\SendEventReminderNotification;
use App\Livewire\Notifications\Bell;
use App\Models\Event;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\EventReminder;
use App\Services\PushNotificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Setting::set('notify_event_reminders_enabled', true);

        $this->user = User::factory()->create([
            'email' => 'pushuser@test.com',
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('member');
        $this->user->memberProfile()->updateOrCreate(
            ['user_id' => $this->user->id],
            [
                'display_name' => 'Push User',
                'onboarding_status' => 'active',
                'onboarding_completed_at' => now(),
            ]
        );
    }

    // ─── Subscription API ─────────────────────────────────

    public function test_user_can_subscribe_to_push_notifications(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.example.com/abc123',
            'keys' => [
                'p256dh' => 'test_public_key',
                'auth' => 'test_auth_token',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'subscribed']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.example.com/abc123',
        ]);
    }

    public function test_user_can_unsubscribe_from_push_notifications(): void
    {
        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.example.com/abc123',
            'public_key' => 'key',
            'auth_token' => 'auth',
        ]);

        $this->actingAs($this->user);

        $response = $this->deleteJson(route('push.unsubscribe'), [
            'endpoint' => 'https://fcm.example.com/abc123',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'unsubscribed']);

        $this->assertDatabaseMissing('push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint' => 'https://fcm.example.com/abc123',
        ]);
    }

    public function test_subscribing_twice_with_same_endpoint_updates_not_duplicates(): void
    {
        $this->actingAs($this->user);

        $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.example.com/abc123',
            'keys' => [
                'p256dh' => 'key_v1',
                'auth' => 'auth_v1',
            ],
        ]);

        $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.example.com/abc123',
            'keys' => [
                'p256dh' => 'key_v2',
                'auth' => 'auth_v2',
            ],
        ]);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'public_key' => 'key_v2',
            'auth_token' => 'auth_v2',
        ]);
    }

    // ─── Push Notification Service ────────────────────────

    public function test_push_notification_service_handles_missing_subscriptions_gracefully(): void
    {
        $service = app(PushNotificationService::class);

        $this->user->pushSubscriptions()->delete();

        $service->send($this->user, 'Test', 'Body');

        $this->expectNotToPerformAssertions();
    }

    public function test_connection_failure_does_not_delete_subscription(): void
    {
        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://invalid.example.com/dead',
            'public_key' => 'key_dead',
            'auth_token' => 'auth_dead',
        ]);

        $service = app(PushNotificationService::class);
        $service->send($this->user, 'Test', 'Body');

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://invalid.example.com/dead',
        ]);
    }

    // ─── Static Assets ────────────────────────────────────

    public function test_manifest_json_is_valid_and_accessible(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $content = json_decode(file_get_contents($manifestPath), true);
        $this->assertSame('The Muhsinat Club', $content['name']);
        $this->assertSame('TMC', $content['short_name']);
        $this->assertSame('standalone', $content['display']);
    }

    public function test_offline_page_loads_without_authentication(): void
    {
        $response = $this->get(route('offline'));

        $response->assertOk();
        $response->assertSee('offline');
    }

    // ─── Event Reminder Gate + Database Notification ──────

    public function test_event_reminder_respects_notification_setting_gate(): void
    {
        Setting::set('notify_event_reminders_enabled', false);

        $event = Event::create([
            'title' => 'Test Event',
            'slug' => 'test-event-'.str()->random(6),
            'description' => 'Description',
            'location_type' => 'online',
            'event_date' => now()->addDay(),
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        Notification::fake();

        $job = new SendEventReminderNotification($this->user, $event);
        $job->handle(app(PushNotificationService::class));

        Notification::assertNothingSent();
    }

    public function test_event_reminder_creates_database_notification(): void
    {
        $event = Event::create([
            'title' => 'Reminder Test Event',
            'slug' => 'reminder-test-'.str()->random(6),
            'description' => 'Test description',
            'location_type' => 'online',
            'event_date' => now()->addDay(),
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        Notification::fake();

        $job = new SendEventReminderNotification($this->user, $event);
        $job->handle(app(PushNotificationService::class));

        Notification::assertSentTo(
            $this->user,
            EventReminder::class,
        );
    }

    // ─── Notification Bell ────────────────────────────────

    public function test_notification_bell_shows_unread_count(): void
    {
        $this->user->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => 'App\Notifications\EventReminder',
            'data' => ['title' => 'Test', 'body' => 'Body'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Bell::class);

        $component->assertSet('unreadCount', 1);
    }

    public function test_notification_bell_marks_as_read_on_click(): void
    {
        $notification = $this->user->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => 'App\Notifications\EventReminder',
            'data' => ['title' => 'Test', 'body' => 'Body'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Bell::class);
        $component->call('markAsRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
        $component->assertSet('unreadCount', 0);
    }

    public function test_notification_bell_shows_recent_notifications(): void
    {
        $this->user->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => 'App\Notifications\EventReminder',
            'data' => ['title' => 'Recent Test', 'body' => 'Recent body'],
        ]);

        $this->actingAs($this->user);

        $component = Livewire::test(Bell::class);

        $recent = $component->get('recent');
        $this->assertCount(1, $recent);
    }

    public function test_notification_bell_shows_view_all_link(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(Bell::class);

        $component->assertSee('View All');
    }

    // ─── Membership Activation Push ─────────────────────────

    public function test_membership_activation_creates_welcome_database_notification(): void
    {
        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://invalid.example.com/welcome-test',
            'public_key' => 'key_welcome',
            'auth_token' => 'auth_welcome',
        ]);

        MembershipActivated::dispatch($this->user, 'TMC-TEST-001', $this->user);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'type' => 'App\Notifications\MembershipPaymentConfirmed',
        ]);
    }

    public function test_membership_activation_preserves_subscription_on_transient_failure(): void
    {
        PushSubscription::create([
            'user_id' => $this->user->id,
            'endpoint' => 'https://invalid.example.com/welcome-test',
            'public_key' => 'key_welcome',
            'auth_token' => 'auth_welcome',
        ]);

        MembershipActivated::dispatch($this->user, 'TMC-TEST-002', $this->user);

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://invalid.example.com/welcome-test',
        ]);
    }
}
