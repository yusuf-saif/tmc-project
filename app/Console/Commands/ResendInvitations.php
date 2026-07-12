<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Legacy command — use `members:send-pending-invites` for post-import sends.
 * This command does not track invited_at and should not be run against future imports.
 */
class ResendInvitations extends Command
{
    protected $signature = 'member:resend-invitations {--dry-run : Show which users would receive the email without sending}';

    protected $description = 'Resend onboarding links to imported members who have not yet completed setup';

    public function handle(): int
    {
        $users = User::whereIn('status', ['onboarding', 'pending_onboarding'])
            ->whereNotNull('email_verified_at')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No imported members found who need onboarding links.');

            return Command::SUCCESS;
        }

        $this->info('Found '.$users->count.' imported member(s) eligible for onboarding link resend.');

        if ($this->option('dry-run')) {
            $this->info('DRY RUN — no emails will be sent:');
            foreach ($users as $user) {
                $this->line("  - {$user->name} <{$user->email}>");
            }

            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($users as $user) {
            $token = Password::broker('onboarding')->createToken($user);
            $membershipId = $user->member_id ?? $user->memberProfile?->membership_id ?? 'N/A';
            Notification::sendNow($user, new OnboardingInvitationNotification($token, $membershipId));
            $sent++;
            $this->line("  Sent to: {$user->name} <{$user->email}>");
        }

        $this->info("Onboarding links sent to {$sent} member(s).");

        return Command::SUCCESS;
    }
}
