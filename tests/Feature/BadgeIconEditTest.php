<?php

namespace Tests\Feature;

use App\Filament\Resources\BadgeResource;
use App\Filament\Resources\BadgeResource\Pages\CreateBadge;
use App\Filament\Resources\BadgeResource\Pages\EditBadge;
use App\Models\Badge;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class BadgeIconEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create([
            'email' => 'icon-admin-'.Str::random(8).'@test.com',
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super_admin');

        return $user;
    }

    protected function badge(string $iconPath = 'badges/icons/abc.png'): Badge
    {
        return Badge::create([
            'name' => 'Badge '.Str::random(6),
            'description' => 'Test badge',
            'criteria' => 'Any criteria',
            'icon_path' => $iconPath,
            'is_active' => true,
        ]);
    }

    public function test_normalize_icon_path_returns_relative_key_unchanged(): void
    {
        $this->assertSame(
            'badges/icons/foo.png',
            Badge::normalizeIconPath('badges/icons/foo.png')
        );
    }

    public function test_normalize_icon_path_strips_full_r2_url(): void
    {
        $this->assertSame(
            'badges/icons/foo.png',
            Badge::normalizeIconPath('https://pub-1234.r2.dev/badges/icons/foo.png')
        );
    }

    public function test_normalize_icon_path_returns_null_for_empty(): void
    {
        $this->assertNull(Badge::normalizeIconPath(null));
        $this->assertNull(Badge::normalizeIconPath(''));
    }

    public function test_edit_page_renders_with_relative_icon_path(): void
    {
        $admin = $this->admin();
        $badge = $this->badge();

        Livewire::actingAs($admin)
            ->test(EditBadge::class, ['record' => $badge->id])
            ->assertSuccessful();
    }

    public function test_edit_page_renders_with_legacy_full_url_icon_path(): void
    {
        $admin = $this->admin();
        $badge = $this->badge('https://pub-1234.r2.dev/badges/icons/legacy.png');

        Livewire::actingAs($admin)
            ->test(EditBadge::class, ['record' => $badge->id])
            ->assertSuccessful();
    }

    public function test_create_page_strips_url_before_save(): void
    {
        $admin = $this->admin();
        $name = 'Badge '.Str::random(6);
        $uuid = (string) Str::uuid();

        Livewire::actingAs($admin)
            ->test(CreateBadge::class)
            ->fillForm([
                'name' => $name,
                'description' => 'desc',
                'criteria' => 'crit',
                'icon_path' => [$uuid => 'https://pub-1234.r2.dev/badges/icons/uploaded.png'],
                'coin_reward' => '0',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('badges', [
            'name' => $name,
            'icon_path' => 'badges/icons/uploaded.png',
        ]);
    }

    public function test_edit_page_strips_url_on_save(): void
    {
        $admin = $this->admin();
        $badge = $this->badge();
        $uuid = (string) Str::uuid();

        Livewire::actingAs($admin)
            ->test(EditBadge::class, ['record' => $badge->id])
            ->fillForm([
                'icon_path' => [$uuid => 'https://pub-1234.r2.dev/badges/icons/replaced.png'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('badges', [
            'id' => $badge->id,
            'icon_path' => 'badges/icons/replaced.png',
        ]);
    }

    public function test_badge_resource_file_upload_has_no_image_resize(): void
    {
        $livewire = new class extends Component implements HasForms
        {
            use InteractsWithForms;
        };

        $form = BadgeResource::form(Forms\Form::make($livewire)->schema([]));
        $field = collect($form->getComponents())->first(
            fn ($component) => $component instanceof Forms\Components\FileUpload && $component->getName() === 'icon_path'
        );

        $this->assertInstanceOf(Forms\Components\FileUpload::class, $field);
        $this->assertNull($field->getImageResizeMode());
        $this->assertNull($field->getImageResizeTargetWidth());
    }
}
