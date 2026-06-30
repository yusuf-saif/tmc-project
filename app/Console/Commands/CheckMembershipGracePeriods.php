<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Services\MembershipStateService;
use Illuminate\Console\Command;

class CheckMembershipGracePeriods extends Command
{
    protected $signature = 'membership:check-grace-periods';

    protected $description = 'Check membership grace periods and suspend expired ones';

    public function handle(MembershipStateService $service): int
    {
        $profiles = MemberProfile::query()
            ->where('onboarding_status', 'member')
            ->whereNotNull('current_period_ends_at')
            ->get();

        $count = 0;

        foreach ($profiles as $profile) {
            $service->checkGracePeriod($profile);
            $count++;
        }

        $this->info("Checked {$count} member profiles for grace period expiry.");

        return Command::SUCCESS;
    }
}
