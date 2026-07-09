<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Services\MembershipIdService;
use Illuminate\Console\Command;

class SyncMembershipCounter extends Command
{
    protected $signature = 'membership:sync-counter
                            {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Sync the membership_serials counter with actual membership IDs in member_profiles';

    public function handle(): int
    {
        $profiles = MemberProfile::query()
            ->whereNotNull('membership_id')
            ->get(['membership_id']);

        if ($profiles->isEmpty()) {
            $this->info('No member profiles with membership IDs found.');

            return Command::SUCCESS;
        }

        $maxSerials = [];

        foreach ($profiles as $profile) {
            if (! preg_match('/^TMC-([A-Z]+)-(\d+)-(\d+)$/', $profile->membership_id, $matches)) {
                $this->warn("Skipping unrecognised ID format: {$profile->membership_id}");

                continue;
            }

            $type = $matches[1];
            $year = (int) $matches[2];
            $serial = (int) $matches[3];

            $key = "{$type}:{$year}";

            if (! isset($maxSerials[$key]) || $serial > $maxSerials[$key]) {
                $maxSerials[$key] = $serial;
            }
        }

        if ($maxSerials === []) {
            $this->info('No parseable membership IDs found.');

            return Command::SUCCESS;
        }

        $this->info('Syncing membership serial counters:');
        $this->table(
            ['Type', 'Hijri Year', 'Max Serial'],
            collect($maxSerials)->map(fn ($serial, $key) => [
                explode(':', $key)[0],
                explode(':', $key)[1],
                $serial,
            ])->values()->toArray()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes made.');

            return Command::SUCCESS;
        }

        foreach ($maxSerials as $key => $serial) {
            [$type, $year] = explode(':', $key);
            MembershipIdService::syncCounter($type, (int) $year, $serial);
            $this->line("  Synced {$type} / {$year} → last_serial = {$serial}");
        }

        $this->info('Counters synced successfully.');

        return Command::SUCCESS;
    }
}
