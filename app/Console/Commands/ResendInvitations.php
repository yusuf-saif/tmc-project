<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SetPasswordNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class ResendInvitations extends Command
{
    protected $signature = 'member:resend-invitations {--dry-run : Show which users would receive the email without sending}';

    protected $description = 'Resend password setup links to imported members who have not yet set a password';

    public function handle(): int
    {
        $users = User::where('status', 'onboarding')
            ->whereNotNull('email_verified_at')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No imported members found who need password setup links.');

            return Command::SUCCESS;
        }

        $this->info("Found {$users->count} imported member(s) eligible for password setup link resend.");

        if ($this->option('dry-run')) {
            $this->info('DRY RUN — no emails will be sent:');
            foreach ($users as $user) {
                $this->line("  - {$user->name} <{$user->email}>");
            }

            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($users as $user) {
            $token = Password::broker()->createToken($user);
            Notification::sendNow($user, new SetPasswordNotification($token));
            $sent++;
            $this->line("  Sent to: {$user->name} <{$user->email}>");
        }

        $this->info("Password setup links sent to {$sent} member(s).");

        return Command::SUCCESS;
    }
}
