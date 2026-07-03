<?php

namespace Tests\Feature;

use App\Services\ImageProcessingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    public function test_resizes_image_when_larger_than_max_width(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg', 1600, 1200);

        $service = app(ImageProcessingService::class);
        $path = $service->resizeAndStore($file, 'avatars', 800);

        Storage::disk('public')->assertExists($path);

        $stored = Storage::disk('public')->get($path);
        $manager = new ImageManager(
            Driver::class,
        );
        $image = $manager->decodeBinary($stored);

        $this->assertLessThanOrEqual(800, $image->width());
    }

    public function test_does_not_upscale_smaller_images(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('small.jpg', 300, 200);

        $service = app(ImageProcessingService::class);
        $path = $service->resizeAndStore($file, 'logos', 800);

        Storage::disk('public')->assertExists($path);

        $stored = Storage::disk('public')->get($path);
        $manager = new ImageManager(
            Driver::class,
        );
        $image = $manager->decodeBinary($stored);

        $this->assertEquals(300, $image->width());
    }
}
