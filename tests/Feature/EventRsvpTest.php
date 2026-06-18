<?php

namespace Tests\Feature;

use App\Jobs\SendEventReminderNotification;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\User;
use App\Services\RsvpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EventRsvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_member_can_view_events_list(): void
    {
        $member = $this->createMember();

        $this->actingAs($member)
            ->get('/events')
            ->assertOk();
    }

    public function test_member_can_rsvp_to_published_event(): void
    {
        $member = $this->createMember();
        $event = $this->createEvent();

        app(RsvpService::class)->rsvp($member, $event);

        $this->assertDatabaseHas('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'cancelled_at' => null,
        ]);
    }

    public function test_member_cannot_rsvp_twice(): void
    {
        $member = $this->createMember();
        $event = $this->createEvent();
        $service = app(RsvpService::class);

        $service->rsvp($member, $event);
        $service->rsvp($member, $event);

        $this->assertSame(1, EventRsvp::query()->where('event_id', $event->id)->where('user_id', $member->id)->active()->count());
        $this->assertSame(1, EventRsvp::query()->where('event_id', $event->id)->where('user_id', $member->id)->count());
    }

    public function test_member_can_cancel_rsvp(): void
    {
        $member = $this->createMember();
        $event = $this->createEvent();
        $service = app(RsvpService::class);

        $service->rsvp($member, $event);
        $service->cancel($member, $event);

        $this->assertDatabaseMissing('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'cancelled_at' => null,
        ]);
        $this->assertNotNull(EventRsvp::query()->where('event_id', $event->id)->where('user_id', $member->id)->value('cancelled_at'));
    }

    public function test_cancelled_event_shows_no_rsvp_button(): void
    {
        $member = $this->createMember();
        $event = $this->createEvent(['status' => 'cancelled']);

        $this->actingAs($member)
            ->get('/events/'.$event->slug)
            ->assertOk()
            ->assertDontSee('RSVP')
            ->assertSee('This event has been cancelled');
    }

    public function test_reminder_job_dispatched_on_rsvp(): void
    {
        Queue::fake();

        $member = $this->createMember();
        $event = $this->createEvent();

        app(RsvpService::class)->rsvp($member, $event);

        Queue::assertPushed(SendEventReminderNotification::class);
    }

    protected function createMember(): User
    {
        $user = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'MEMB'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);
        $user->assignRole('member');
        $user->profile()->create([
            'display_name' => $user->name,
            'membership_status' => 'active',
            'onboarding_completed_at' => now(),
        ]);

        return $user;
    }

    protected function createEvent(array $overrides = []): Event
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMN'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        ]);

        return Event::query()->create(array_merge([
            'title' => 'Event '.str()->random(6),
            'description' => '<p>Event description</p>',
            'location_type' => 'online',
            'location_detail' => 'Zoom',
            'event_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHour(),
            'status' => 'published',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ], $overrides));
    }
}
