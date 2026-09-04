<?php

namespace App\Console\Commands;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupR2Storage extends Command
{
    protected $signature = 'r2:setup
        {--cors-only : Only configure CORS, skip lifecycle rule}
        {--lifecycle-only : Only configure lifecycle rule, skip CORS}';

    protected $description = 'Configure R2 bucket CORS policy and livewire-tmp lifecycle rule for direct browser uploads';

    private const LIVEWIRE_TMP_PREFIX = 'livewire-tmp/';

    private const LIFECYCLE_EXPIRY_DAYS = 1;

    public function handle(): int
    {
        $exitCode = Command::SUCCESS;

        if (! $this->option('lifecycle-only')) {
            $corsResult = $this->configureCors();
            if ($corsResult === false) {
                $exitCode = Command::FAILURE;
            }
        }

        if (! $this->option('cors-only')) {
            $lifecycleResult = $this->configureLifecycleRule();
            if ($lifecycleResult === false) {
                $exitCode = Command::FAILURE;
            }
        }

        return $exitCode;
    }

    private function configureCors(): bool
    {
        $this->info('Configuring R2 CORS policy...');

        $apiToken = config('services.cloudflare.api_token');
        $accountId = config('services.cloudflare.account_id');
        $bucketName = config('filesystems.disks.r2.bucket');

        if (empty($apiToken) || empty($accountId) || empty($bucketName)) {
            $this->warn('Skipping CORS: CLOUDFLARE_API_TOKEN, CLOUDFLARE_ACCOUNT_ID, or AWS_BUCKET not set. Configure in .env to enable auto-CORS.');

            return true;
        }

        $origin = config('app.url', 'http://localhost:8000');
        $origins = [$origin, 'http://127.0.0.1:8000', 'http://localhost:8000'];
        $origins = array_unique(array_filter($origins));

        $corsRules = [
            [
                'AllowedHeaders' => ['*'],
                'AllowedMethods' => ['GET', 'PUT', 'POST', 'HEAD'],
                'AllowedOrigins' => $origins,
                'ExposeHeaders' => ['ETag'],
                'MaxAgeSeconds' => 3000,
            ],
        ];

        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/r2/buckets/{$bucketName}/cors";

        try {
            $response = Http::withToken($apiToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->put($url, $corsRules);

            if ($response->successful()) {
                $this->info('CORS policy applied successfully.');

                return true;
            }

            $this->error('Cloudflare API returned HTTP '.$response->status());
            $this->error($response->body());

            return false;
        } catch (\Throwable $e) {
            $this->error('Failed to configure CORS: '.$e->getMessage());

            return false;
        }
    }

    private function configureLifecycleRule(): bool
    {
        $this->info('Configuring livewire-tmp lifecycle rule...');

        $endpoint = config('filesystems.disks.r2.endpoint');
        $key = config('filesystems.disks.r2.key');
        $secret = config('filesystems.disks.r2.secret');
        $region = config('filesystems.disks.r2.region', 'auto');
        $bucket = config('filesystems.disks.r2.bucket');
        $usePathStyle = config('filesystems.disks.r2.use_path_style_endpoint', false);

        if (empty($endpoint) || empty($key) || empty($secret) || empty($bucket)) {
            $this->warn('Skipping lifecycle rule: R2 S3 credentials not fully configured. Check AWS_ENDPOINT, AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, and AWS_BUCKET.');

            return true;
        }

        try {
            $s3 = new S3Client([
                'version' => 'latest',
                'region' => $region,
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => $usePathStyle,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);

            $existingRules = [];

            try {
                $result = $s3->getBucketLifecycleConfiguration(['Bucket' => $bucket]);
                $existingRules = $result->get('Rules') ?? [];
            } catch (AwsException $e) {
                if (str_contains($e->getAwsErrorMessage(), 'NoSuchLifecycleConfiguration')) {
                    $existingRules = [];
                } else {
                    throw $e;
                }
            }

            $filteredRules = array_values(array_filter($existingRules, function ($rule) {
                return ($rule['Filter']['Prefix'] ?? null) !== self::LIVEWIRE_TMP_PREFIX;
            }));

            $filteredRules[] = [
                'ID' => 'livewire-tmp-cleanup',
                'Status' => 'Enabled',
                'Filter' => [
                    'Prefix' => self::LIVEWIRE_TMP_PREFIX,
                ],
                'Expiration' => [
                    'Days' => self::LIFECYCLE_EXPIRY_DAYS,
                ],
            ];

            $s3->putBucketLifecycleConfiguration([
                'Bucket' => $bucket,
                'LifecycleConfiguration' => [
                    'Rules' => $filteredRules,
                ],
            ]);

            $this->info('Lifecycle rule applied: objects in livewire-tmp/ expire after '.self::LIFECYCLE_EXPIRY_DAYS.' day(s).');

            return true;
        } catch (\Throwable $e) {
            $this->error('Failed to configure lifecycle rule: '.$e->getMessage());

            return false;
        }
    }
}
