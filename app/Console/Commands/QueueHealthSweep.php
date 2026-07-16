<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\QueueHealthAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Sentry\Laravel\Facade as Sentry;

class QueueHealthSweep extends Command
{
    protected $signature = 'queue:health-sweep {--no-drain : Alert only, do not drain stuck jobs}';

    protected $description = 'Check for delayed queue jobs, alert admins, and optionally drain stuck jobs';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(15);
        $delayedCount = DB::table('jobs')->where('created_at', '<', $cutoff)->count();

        if ($delayedCount === 0) {
            $this->info('No delayed jobs detected.');

            return Command::SUCCESS;
        }

        $this->warn("{$delayedCount} delayed job(s) detected.");

        try {
            Sentry::captureMessage(
                "{$delayedCount} delayed queue jobs detected",
                \Sentry\Severity::warning,
            );
        } catch (\Throwable) {
            // Sentry is best-effort; don't fail the command if it's unavailable
        }

        $drainReport = null;

        if (! $this->option('no-drain')) {
            $this->info('Draining stuck jobs...');
            Artisan::call('queue:work', [
                '--queue' => 'default,membership,billing',
                '--stop-when-empty' => true,
                '--max-time' => 300,
                '--tries' => 3,
                '--timeout' => 300,
            ]);
            $drainReport = trim(Artisan::output());
            $this->info("Drain complete. Output:\n{$drainReport}");
        }

        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'admin', 'moderator', 'content_editor']);
        })->get();

        foreach ($admins as $admin) {
            Notification::send($admin, new QueueHealthAlertNotification($delayedCount, $drainReport));
        }

        $this->info("Alerted {$admins->count()} admin user(s).");

        return Command::SUCCESS;
    }
}
