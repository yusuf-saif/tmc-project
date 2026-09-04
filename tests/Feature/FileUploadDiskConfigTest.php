<?php

namespace Tests\Feature;

use App\Filament\Resources\BadgeResource;
use App\Filament\Resources\CommunitySpaceResource;
use App\Filament\Resources\EventResource;
use App\Filament\Resources\ResourceResource;
use App\Filament\Resources\SouqListingResource;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Tests\TestCase;

class FileUploadDiskConfigTest extends TestCase
{
    public function test_livewire_temp_upload_disk_defaults_to_local_when_env_unset(): void
    {
        config(['livewire.temporary_file_upload.disk' => 'local']);

        $this->assertSame('local', config('livewire.temporary_file_upload.disk'));
    }

    public function test_livewire_temp_upload_disk_resolves_to_r2_when_env_set(): void
    {
        config(['livewire.temporary_file_upload.disk' => 'r2']);

        $this->assertSame('r2', config('livewire.temporary_file_upload.disk'));
    }

    public function test_r2_disk_uses_s3_driver(): void
    {
        $diskConfig = config('filesystems.disks.r2');

        $this->assertNotNull($diskConfig, 'r2 disk must be configured in config/filesystems.php');
        $this->assertSame('s3', $diskConfig['driver']);
    }

    public function test_event_resource_cover_image_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(EventResource::class, 'cover_image_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_souq_listing_logo_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(SouqListingResource::class, 'logo_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_badge_icon_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(BadgeResource::class, 'icon_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_community_space_cover_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(CommunitySpaceResource::class, 'cover_image_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_resource_file_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(ResourceResource::class, 'file_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_resource_thumbnail_targets_r2_disk(): void
    {
        $field = $this->getFileUploadField(ResourceResource::class, 'thumbnail_path');

        $this->assertSame('r2', $field->getDiskName());
    }

    public function test_r2_disk_has_public_visibility(): void
    {
        $diskConfig = config('filesystems.disks.r2');

        $this->assertSame('public', $diskConfig['visibility']);
    }

    private function getFileUploadField(string $resourceClass, string $fieldName): Forms\Components\FileUpload
    {
        $livewire = new class extends Component implements HasForms
        {
            use InteractsWithForms;
        };

        $form = $resourceClass::form(Forms\Form::make($livewire)->schema([]));

        $field = collect($form->getComponents())->first(
            fn ($component) => $component instanceof Forms\Components\FileUpload && $component->getName() === $fieldName
        );

        $this->assertInstanceOf(Forms\Components\FileUpload::class, $field, "{$fieldName} FileUpload field must exist on {$resourceClass}");

        return $field;
    }
}
