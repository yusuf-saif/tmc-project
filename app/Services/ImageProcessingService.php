<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(\Intervention\Image\Drivers\Gd\Driver::class);
    }

    public function resizeAndStore(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 800,
        string $disk = 'public'
    ): string {
        $image = $this->manager->decodePath($file->getRealPath());

        if ($image->width() > $maxWidth) {
            $image->scale(width: $maxWidth);
        }

        $filename = $directory . '/' . uniqid() . '.' . $file->getClientOriginalExtension();

        Storage::disk($disk)->put(
            $filename,
            (string) $image->encode()
        );

        return $filename;
    }
}
