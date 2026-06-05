<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\User;
use App\Livewire\Resources\ResourceDetail;
use App\Services\DuaListService;
use Database\Seeders\ResourceSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        User::factory()->create([
            'status' => 'active',
            'referral_code' => 'ADMIN200',
        ]);

        $this->seed(ResourceSeeder::class);

        $this->member = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'MEMB2001',
        ]);
        $this->member->assignRole('member');
        $this->member->profile()->create([
            'display_name' => $this->member->name,
            'onboarding_completed_at' => now(),
        ]);
    }

    public function test_member_can_browse_resources(): void
    {
        $this->actingAs($this->member)
            ->get('/resources')
            ->assertOk()
            ->assertSee('Resources');
    }

    public function test_member_can_filter_by_category(): void
    {
        $this->actingAs($this->member)
            ->get('/resources?category=dua_book')
            ->assertOk()
            ->assertSee("Du'a for Beginning")
            ->assertDontSee('Finding Sakīnah in the Chaos');
    }

    public function test_dua_resource_shows_save_button(): void
    {
        $resource = Resource::query()->where('type', 'dua')->firstOrFail();

        $this->actingAs($this->member)
            ->get('/resources/'.$resource->slug)
            ->assertOk()
            ->assertSeeText('Save to My Du');
    }

    public function test_member_can_save_dua_to_list(): void
    {
        $resource = Resource::query()->where('type', 'dua')->firstOrFail();

        Livewire::actingAs($this->member)
            ->test(ResourceDetail::class, ['slug' => $resource->slug])
            ->call('saveToDuaList');

        $this->assertDatabaseHas('dua_list_items', [
            'user_id' => $this->member->id,
            'resource_id' => $resource->id,
        ]);
    }

    public function test_dua_toggle_removes_item(): void
    {
        $resource = Resource::query()->where('type', 'dua')->firstOrFail();

        Livewire::actingAs($this->member)
            ->test(ResourceDetail::class, ['slug' => $resource->slug])
            ->call('saveToDuaList')
            ->call('removeFromDuaList');

        $this->assertSoftDeleted('dua_list_items', [
            'user_id' => $this->member->id,
            'resource_id' => $resource->id,
        ]);
    }

    public function test_save_dua_is_idempotent(): void
    {
        $resource = Resource::query()->where('type', 'dua')->firstOrFail();
        $service = app(DuaListService::class);

        $service->save($this->member, $resource);
        $service->save($this->member, $resource);

        $this->assertSame(1, $this->member->duaListItems()->where('resource_id', $resource->id)->count());
    }

    public function test_member_can_add_manual_dua(): void
    {
        app(DuaListService::class)->saveManual($this->member, 'Arabic text', 'Morning');

        $this->assertDatabaseHas('dua_list_items', [
            'user_id' => $this->member->id,
            'dua_text' => 'Arabic text',
            'label' => 'Morning',
        ]);
    }

    public function test_resaving_after_remove_restores_dua_item(): void
    {
        $resource = Resource::query()->where('type', 'dua')->firstOrFail();
        $service = app(DuaListService::class);

        $service->save($this->member, $resource);
        $item = $this->member->duaListItems()->where('resource_id', $resource->id)->firstOrFail();
        $service->remove($this->member, $item);
        $service->save($this->member, $resource);

        $this->assertDatabaseHas('dua_list_items', [
            'user_id' => $this->member->id,
            'resource_id' => $resource->id,
            'deleted_at' => null,
        ]);
        $this->assertSame(1, $this->member->duaListItems()->where('resource_id', $resource->id)->count());
    }
}
