<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function createEvent(User $user, string $slug = 'test-event'): Event
    {
        return Event::create([
            'title' => 'Test Event',
            'slug' => $slug,
            'description' => 'An event description',
            'location_type' => 'online',
            'event_date' => now()->addDays(5),
            'status' => 'draft',
            'created_by' => $user->id,
        ]);
    }

    public function test_admin_can_load_events_resource_pages(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN001',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin/events')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/events/create')
            ->assertOk();
    }

    public function test_admin_can_open_create_and_edit_pages(): void
    {
        $admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN002',
        ]);
        $admin->assignRole('admin');

        $event = $this->createEvent($admin);

        $this->actingAs($admin)
            ->get('/admin/events/'.$event->id.'/edit')
            ->assertOk();
    }

    public function test_content_editor_cannot_create_or_edit_others_events(): void
    {
        $editor = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'EDITOR01',
        ]);
        $editor->assignRole('content_editor');

        $admin = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN003',
        ]);
        $admin->assignRole('admin');

        $othersEvent = $this->createEvent($admin, 'others-event');
        $ownEvent = $this->createEvent($editor, 'own-event');

        $this->actingAs($editor)
            ->get('/admin/events/create')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/events/'.$othersEvent->id.'/edit')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/events/'.$ownEvent->id.'/edit')
            ->assertOk();
    }
}
