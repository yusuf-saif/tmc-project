<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\ImportCompleteNotification;
use App\Notifications\ImportFailedNotification;
use App\Services\MembersCsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    // Procfile global default is --tries=3, but an import must never auto-retry
    // and double-insert. Job-level $tries wins over the worker default.
    public int $tries = 1;

    public function __construct(
        public string $csvPath,
        public int $userId,
        public string $disk = 'local',
    ) {}

    public function handle(): void
    {
        $service = app(MembersCsvImportService::class);
        $result = $service->import($this->csvPath, $this->disk);

        User::find($this->userId)?->notify(
            new ImportCompleteNotification($result)
        );

        Storage::disk($this->disk)->delete($this->csvPath);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportMembersJob failed', [
            'csv' => $this->csvPath,
            'error' => $exception->getMessage(),
        ]);

        User::find($this->userId)?->notify(
            new ImportFailedNotification($this->csvPath, $exception->getMessage())
        );

        Storage::disk($this->disk)->delete($this->csvPath);
    }
}
