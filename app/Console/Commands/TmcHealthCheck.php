<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TmcHealthCheck extends Command
{
    protected $signature = 'tmc:health-check';

    protected $description = 'Run a comprehensive health check on the TMC application';

    protected int $failures = 0;

    public function handle(): int
    {
        $this->components->info('TMC Health Check');
        $this->newLine();

        $this->checkDatabaseConnection();
        $this->checkMigrations();
        $this->checkQueueConfig();
        $this->checkFailedJobs();
        $this->checkStorageLink();
        $this->checkPaystackConfig();
        $this->checkMailConfig();
        $this->checkAppKey();
        $this->checkSeedData();
        $this->checkWritableDirectories();

        $this->newLine();

        if ($this->failures === 0) {
            $this->components->success('All checks passed.');

            return Command::SUCCESS;
        }

        $this->components->error("{$this->failures} check(s) failed.");

        return Command::FAILURE;
    }

    protected function checkDatabaseConnection(): void
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $this->components->twoColumnDetail('Database Connection', '<fg=green>OK</>');
        } catch (\Throwable $e) {
            $this->failures++;
            $this->components->twoColumnDetail('Database Connection', '<fg=red>FAIL</>');
            $this->components->bulletList([$e->getMessage()]);
        }
    }

    protected function checkMigrations(): void
    {
        try {
            $exitCode = Artisan::call('migrate:status', ['--no-ansi' => true]);
            $output = Artisan::output();

            if (preg_match('/\b(N|No)\b/', $output)) {
                $this->failures++;
                $this->components->twoColumnDetail('Migrations', '<fg=red>FAIL</> (pending migrations)');

                return;
            }

            $this->components->twoColumnDetail('Migrations', '<fg=green>OK</>');
        } catch (\Throwable $e) {
            $this->failures++;
            $this->components->twoColumnDetail('Migrations', '<fg=red>FAIL</>');
            $this->components->bulletList([$e->getMessage()]);
        }
    }

    protected function checkQueueConfig(): void
    {
        $driver = config('queue.default');
        $isProduction = app()->environment('production');

        if ($isProduction && $driver !== 'database') {
            $this->failures++;
            $this->components->twoColumnDetail('Queue Config', "<fg=red>FAIL</> (expected 'database', got '{$driver}')");

            return;
        }

        $this->components->twoColumnDetail('Queue Config', "<fg=green>OK</> (driver={$driver})");
    }

    protected function checkFailedJobs(): void
    {
        try {
            $count = DB::table('failed_jobs')->count();

            if ($count > 0) {
                $this->failures++;
                $this->components->twoColumnDetail('Failed Jobs', "<fg=red>{$count} failed job(s)</>");

                return;
            }

            $this->components->twoColumnDetail('Failed Jobs', '<fg=green>0</>');
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('Failed Jobs', '<fg=yellow>SKIP</> (table not found)');
        }
    }

    protected function checkStorageLink(): void
    {
        if (file_exists(public_path('storage'))) {
            $this->components->twoColumnDetail('Storage Link', '<fg=green>OK</>');
        } elseif (app()->environment('production')) {
            $this->failures++;
            $this->components->twoColumnDetail('Storage Link', '<fg=red>FAIL</> (run `php artisan storage:link`)');
        } else {
            $this->components->twoColumnDetail('Storage Link', '<fg=yellow>SKIP</> (not linked in this env)');
        }
    }

    protected function checkPaystackConfig(): void
    {
        if (! config('payments.enabled', true)) {
            $this->components->twoColumnDetail('Paystack Config', '<fg=yellow>SKIP</> (payments disabled)');

            return;
        }

        $isProduction = app()->environment('production');

        $secret = Config::get('paystack.secretKey');
        $public = Config::get('paystack.publicKey');
        $webhook = Config::get('paystack.webhookSecret');

        $checks = [
            ['label' => 'Paystack Secret Key', 'value' => $secret],
            ['label' => 'Paystack Public Key', 'value' => $public],
            ['label' => 'Paystack Webhook Secret', 'value' => $webhook],
        ];

        foreach ($checks as $check) {
            $empty = empty($check['value']) || str_starts_with((string) $check['value'], 'YOUR_');

            if ($empty && $isProduction) {
                $this->failures++;
                $this->components->twoColumnDetail($check['label'], '<fg=red>FAIL</> (not configured)');
            } elseif ($empty) {
                $this->components->twoColumnDetail($check['label'], '<fg=yellow>SKIP</> (not set in this env)');
            } else {
                $this->components->twoColumnDetail($check['label'], '<fg=green>OK</>');
            }
        }
    }

    protected function checkMailConfig(): void
    {
        $mailer = Config::get('mail.default');
        $fromAddress = Config::get('mail.from.address');

        $ok = true;
        $issues = [];

        if (empty($mailer)) {
            $ok = false;
            $issues[] = 'MAIL_MAILER not configured';
        }

        if (empty($fromAddress)) {
            $ok = false;
            $issues[] = 'MAIL_FROM_ADDRESS not configured';
        }

        if ($ok) {
            $this->components->twoColumnDetail('Mail Config', "<fg=green>OK</> (mailer={$mailer})");
        } else {
            $this->failures++;
            $this->components->twoColumnDetail('Mail Config', '<fg=red>FAIL</>');
            $this->components->bulletList($issues);
        }
    }

    protected function checkAppKey(): void
    {
        $key = Config::get('app.key');

        if (empty($key) || $key === 'base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=') {
            $this->failures++;
            $this->components->twoColumnDetail('App Key', '<fg=red>FAIL</> (not configured)');
        } else {
            $this->components->twoColumnDetail('App Key', '<fg=green>OK</>');
        }
    }

    protected function checkSeedData(): void
    {
        try {
            $roleCount = Role::count();

            if ($roleCount < 6) {
                $this->failures++;
                $this->components->twoColumnDetail('Seed Data (Roles)', "<fg=red>FAIL</> ({$roleCount} roles, expected 6+)");

                return;
            }

            $this->components->twoColumnDetail('Seed Data (Roles)', "<fg=green>{$roleCount} roles</>");
        } catch (\Throwable $e) {
            $this->components->twoColumnDetail('Seed Data (Roles)', '<fg=yellow>SKIP</> (table not found)');
        }
    }

    protected function checkWritableDirectories(): void
    {
        $dirs = [
            storage_path('logs') => 'storage/logs',
            storage_path('app/public') => 'storage/app/public',
            storage_path('app/public/payment-proofs') => 'storage/app/public/payment-proofs',
            storage_path('framework/cache/data') => 'storage/framework/cache/data',
            storage_path('framework/views') => 'storage/framework/views',
        ];

        $allWritable = true;
        $badDirs = [];

        foreach ($dirs as $path => $label) {
            if (! is_dir($path)) {
                continue;
            }

            if (! is_writable($path)) {
                $allWritable = false;
                $badDirs[] = $label;
            }
        }

        if ($allWritable) {
            $this->components->twoColumnDetail('Writable Directories', '<fg=green>OK</>');
        } else {
            $this->failures++;
            $this->components->twoColumnDetail('Writable Directories', '<fg=red>FAIL</>');
            $this->components->bulletList($badDirs);
        }
    }
}
