<?php

namespace Tests\Feature;

use App\Console\Commands\SetupR2Storage;
use Tests\TestCase;

class SetupR2StorageTest extends TestCase
{
    public function test_skips_cors_when_cloudflare_credentials_missing(): void
    {
        config([
            'services.cloudflare.api_token' => null,
            'services.cloudflare.account_id' => null,
            'filesystems.disks.r2.bucket' => null,
        ]);

        $this->artisan(SetupR2Storage::class, ['--lifecycle-only' => true])
            ->doesntExpectOutputToContain('FAIL')
            ->assertExitCode(0);
    }

    public function test_command_is_registered(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('r2:setup');
    }
}
