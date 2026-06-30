<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Notifications\MembershipRenewalReminder;
use Illuminate\Console\Command;

class SendMembershipRenewalReminders extends Command
{
    protected $signature = 'membership:send-renewal-reminders';

    protected $description = 'Send renewal reminders to members whose period ends within 7 days';

    public function handle(): int
    {
        $profiles = MemberProfile::query()
            ->where('onboarding_status', 'member')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '>=', now())
            ->where('current_period_ends_at', '<=', now()->addDays(7))
            ->where(function ($q) {
                $q->whereNull('reminder_sent_at')
                  ->orWhere('reminder_sent_at', '<', \DB::raw('current_period_ends_at'));
            })
            ->get();

        $sent = 0;

        foreach ($profiles as $profile) {
            $profile->user?->notify(new MembershipRenewalReminder($profile));
            $profile->reminder_sent_at = now();
            $profile->save();
            $sent++;
        }

        $this->info("Sent {$sent} membership renewal reminder(s).");

        return Command::SUCCESS;
    }
}
