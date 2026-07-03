<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Models\Setting;
use App\Notifications\MembershipRenewalReminder;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendMembershipRenewalReminders extends Command
{
    protected $signature = 'membership:send-renewal-reminders';

    protected $description = 'Send renewal reminders to members whose period ends within 7 days';

    public function handle(): int
    {
        if (! (bool) Setting::get('notify_renewal_reminders_enabled')) {
            $this->info('Renewal reminders are disabled via settings.');

            return Command::SUCCESS;
        }

        $reminderDays = (int) Setting::get('membership_reminder_days_before');

        $profiles = MemberProfile::query()
            ->where('onboarding_status', 'member')
            ->whereNotNull('current_period_ends_at')
            ->where('current_period_ends_at', '>=', now())
            ->where('current_period_ends_at', '<=', now()->addDays($reminderDays))
            ->where(function ($q) {
                $q->whereNull('reminder_sent_at')
                    ->orWhere('reminder_sent_at', '<', \DB::raw('current_period_ends_at'));
            })
            ->get();

        $sent = 0;
        $pushService = app(PushNotificationService::class);

        foreach ($profiles as $profile) {
            $user = $profile->user;
            if (! $user) {
                continue;
            }

            $user->notify(new MembershipRenewalReminder($profile));
            $pushService->send(
                $user,
                'Renew Your Membership',
                'Your membership period is ending soon. Renew now to keep your access active.',
                route('home'),
            );
            $profile->reminder_sent_at = now();
            $profile->save();
            $sent++;
        }

        $this->info("Sent {$sent} membership renewal reminder(s).");

        return Command::SUCCESS;
    }
}
