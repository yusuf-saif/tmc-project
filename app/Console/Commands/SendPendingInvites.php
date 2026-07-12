<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\OnboardingInvitationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

class SendPendingInvites extends Command
{
    protected $signature = 'members:send-pending-invites {--limit=90} {--dry-run}';

    protected $description = 'Send onboarding invitations to pending users who have not yet been invited (invited_at IS NULL)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $users = User::whereNull('invited_at')
            ->where('status', 'pending_onboarding')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No pending users found without an invite.');

            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} user(s) eligible for invitation.");

        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Email'],
                $users->map(fn ($u) => [$u->id, $u->email])->all()
            );

            return Command::SUCCESS;
        }

        $invited = 0;

        foreach ($users as $user) {
            $token = Password::broker('onboarding')->createToken($user);
            $membershipId = $user->member_id ?? $user->memberProfile?->membership_id ?? 'N/A';
            $user->notify(new OnboardingInvitationNotification($token, $membershipId));
            $user->update(['invited_at' => now()]);
            $invited++;
            $this->line("  Invited: {$user->email}");
        }

        $this->info("Done. Invited {$invited} user(s).");

        return Command::SUCCESS;
    }
}
