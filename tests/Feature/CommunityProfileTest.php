<?php

namespace Tests\Feature;

use App\Livewire\Community\SupportForm;
use App\Livewire\Profile\NotificationPreferences;
use App\Models\Announcement;
use App\Models\CommunitySpace;
use App\Models\SupportApplication;
use App\Models\User;
use Database\Seeders\CommunitySeeder;
use Database\Seeders\GoalSeeder;
use Database\Seeders\InterestSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            InterestSeeder::class,
            GoalSeeder::class,
            CommunitySeeder::class,
        ]);

        $this->member = $this->createOnboardedUser('member@example.com', 'MEMB6001', 'member');
        $this->admin = $this->createOnboardedUser('admin@example.com', 'ADMIN600', 'admin');
    }

    public function test_member_can_view_community_page(): void
    {
        $this->actingAs($this->member)
            ->get('/community')
            ->assertOk();
    }

    public function test_only_active_spaces_shown(): void
    {
        CommunitySpace::query()->create([
            'name' => 'Inactive Space',
            'short_description' => 'Should stay hidden',
            'description' => 'Hidden',
            'is_active' => false,
        ]);

        $this->actingAs($this->member)
            ->get('/community')
            ->assertDontSee('Inactive Space');
    }

    public function test_member_can_view_space_detail(): void
    {
        $space = CommunitySpace::query()->firstOrFail();

        $this->actingAs($this->member)
            ->get('/community/spaces/'.$space->slug)
            ->assertOk();
    }

    public function test_member_can_submit_volunteer_application(): void
    {
        Livewire::actingAs($this->member)
            ->test(SupportForm::class, ['type' => 'volunteer'])
            ->set('name', 'Aisha Volunteer')
            ->set('email', 'member@example.com')
            ->set('skillsOrFocus', 'Project coordination and hospitality.')
            ->set('motivation', 'I want to serve the sisterhood with consistency.')
            ->set('availability', 'Weekends')
            ->call('submit');

        $this->assertDatabaseHas('support_applications', [
            'user_id' => $this->member->id,
            'type' => 'volunteer',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_support_application_prevented(): void
    {
        SupportApplication::query()->create([
            'user_id' => $this->member->id,
            'type' => 'volunteer',
            'name' => $this->member->name,
            'email' => $this->member->email,
            'motivation' => 'I would love to help.',
            'skills_or_focus' => 'Admin support',
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->member)
            ->test(SupportForm::class, ['type' => 'volunteer'])
            ->assertSet('existing.id', SupportApplication::query()->firstOrFail()->id);
    }

    public function test_member_can_view_profile(): void
    {
        $this->actingAs($this->member)
            ->get('/profile')
            ->assertOk();
    }

    public function test_legacy_card_loads(): void
    {
        $this->actingAs($this->member)
            ->get('/profile/legacy-card')
            ->assertOk();
    }

    public function test_legacy_card_contains_member_name(): void
    {
        $this->member->profile()->update(['display_name' => 'Legacy Aisha']);

        $this->actingAs($this->member)
            ->get('/profile/legacy-card')
            ->assertSee('Legacy Aisha');
    }

    public function test_notification_preferences_save(): void
    {
        Livewire::actingAs($this->member)
            ->test(NotificationPreferences::class)
            ->set('events', false)
            ->set('announcements', true)
            ->set('coins', false)
            ->set('community', true)
            ->call('save');

        $this->member->refresh();

        $this->assertSame([
            'events' => false,
            'announcements' => true,
            'coins' => false,
            'community' => true,
        ], $this->member->profile->notification_preferences);
    }

    public function test_announcement_appears_on_home(): void
    {
        Announcement::query()->create([
            'title' => 'Member Update',
            'body' => 'A warm update for all members.',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->member)
            ->get('/home')
            ->assertSee('Member Update');
    }

    public function test_scheduled_announcement_publishes(): void
    {
        $announcement = Announcement::query()->create([
            'title' => 'Scheduled Update',
            'body' => 'Soon to be published.',
            'status' => 'scheduled',
            'publish_at' => now()->subMinute(),
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        Artisan::call('schedule:run');

        $announcement->refresh();

        $this->assertSame('published', $announcement->status);
        $this->assertNotNull($announcement->published_at);
    }

    public function test_admin_can_load_phase_six_filament_resources(): void
    {
        $this->actingAs($this->admin)->get('/admin/community-spaces')->assertOk();
        $this->actingAs($this->admin)->get('/admin/support-applications')->assertOk();
        $this->actingAs($this->admin)->get('/admin/announcements')->assertOk();
    }

    protected function createOnboardedUser(string $email, string $referralCode, string $role): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
            'status' => 'active',
            'referral_code' => $referralCode,
        ]);

        $user->assignRole($role);
        $user->profile()->create([
            'display_name' => $user->name,
            'onboarding_completed_at' => now(),
            'notification_preferences' => [
                'events' => true,
                'announcements' => true,
                'coins' => true,
                'community' => true,
            ],
        ]);

        return $user;
    }
}
