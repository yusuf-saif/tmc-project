<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(Driver::class);
    }

    public function resizeAndStore(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 800,
        string $disk = 'r2'
    ): string {
        $image = $this->manager->decodePath($file->getRealPath());

        if ($image->width() > $maxWidth) {
            $image->scale(width: $maxWidth);
        }

        $filename = $directory.'/'.bin2hex(random_bytes(16)).'.'.$file->getClientOriginalExtension();

        $extension = strtolower($file->getClientOriginalExtension());
        $encoder = match ($extension) {
            'png' => new PngEncoder,
            'webp' => new WebpEncoder,
            default => new JpegEncoder(quality: 80),
        };

        Storage::disk($disk)->put(
            $filename,
            (string) $image->encode($encoder),
        );

        return $filename;
    }
}
