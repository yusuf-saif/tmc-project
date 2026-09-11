<?php

namespace Tests\Feature;

use App\Livewire\Home\HomeDashboard;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ResourceDiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class]);
    }

    // ─── Community entry point ─────────────────────────────────────

    public function test_community_landing_links_to_resources_library(): void
    {
        $user = $this->createMemberUser();

        $this->actingAs($user)
            ->get('/community')
            ->assertOk()
            ->assertSee(route('resources'));
    }

    // ─── Home widget: visibility window ────────────────────────────

    public function test_home_shows_widget_for_resource_published_within_48_hours(): void
    {
        $resource = $this->createResource(['created_at' => now()->subHours(47)]);

        $html = Livewire::actingAs($this->createMemberUser())
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringContainsString('New Resource', $html);
        $this->assertStringContainsString($resource->title, $html);
        $this->assertStringContainsString(route('resources.show', $resource->slug), $html);
    }

    public function test_home_hides_widget_once_more_than_48_hours_old(): void
    {
        $resource = $this->createResource(['created_at' => now()->subHours(49)]);

        $html = Livewire::actingAs($this->createMemberUser())
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringNotContainsString('New Resource', $html);
        $this->assertStringNotContainsString($resource->title, $html);
        $this->assertStringNotContainsString(route('resources.show', $resource->slug), $html);
    }

    public function test_home_hides_widget_when_no_recent_resources_exist(): void
    {
        $html = Livewire::actingAs($this->createMemberUser())
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringNotContainsString('New Resource', $html);
    }

    public function test_home_hides_widget_for_unpublished_draft_created_recently(): void
    {
        $resource = $this->createResource([
            'status' => 'draft',
            'created_at' => now()->subHours(1),
        ]);

        $html = Livewire::actingAs($this->createMemberUser())
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringNotContainsString('New Resource', $html);
        $this->assertStringNotContainsString($resource->title, $html);
    }

    public function test_home_widget_links_to_the_most_recent_resource_when_multiple_recent(): void
    {
        $newer = $this->createResource(['created_at' => now()->subHours(2)]);
        $older = $this->createResource(['created_at' => now()->subHours(30)]);

        $html = Livewire::actingAs($this->createMemberUser())
            ->test(HomeDashboard::class)
            ->html();

        $this->assertStringContainsString(route('resources.show', $newer->slug), $html);
        $this->assertStringNotContainsString(route('resources.show', $older->slug), $html);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function createMemberUser(): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);
        $user->assignRole('member');
        $user->memberProfile()->updateOrCreate(
            ['user_id' => $user->id],
            ['display_name' => $user->name, 'onboarding_status' => 'active'],
        );

        return $user->fresh();
    }

    protected function createResource(array $attributes = []): Resource
    {
        $author = User::factory()->create(['status' => 'active']);

        $resource = Resource::query()->create(array_merge([
            'title' => 'Resource '.Str::random(6),
            'slug' => Str::random(16),
            'description' => 'A resource description for the sisterhood',
            'type' => 'article',
            'status' => 'published',
            'created_by' => $author->id,
            'updated_by' => $author->id,
        ], $attributes));

        if (array_key_exists('created_at', $attributes)) {
            $resource->created_at = $attributes['created_at'];
            $resource->save();
        }

        return $resource->fresh();
    }
}
