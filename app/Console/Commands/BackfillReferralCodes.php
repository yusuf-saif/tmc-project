<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackfillReferralCodes extends Command
{
    protected $signature = 'member:backfill-referral-codes {--dry-run : Show which users would be updated without changing anything}';

    protected $description = 'Generate referral codes for existing users who have none';

    public function handle(): int
    {
        $users = User::whereNull('referral_code')->get();

        if ($users->isEmpty()) {
            $this->info('All users already have a referral code.');

            return Command::SUCCESS;
        }

        $this->line("Found {$users->count()} user(s) without a referral code.");

        if ($this->option('dry-run')) {
            $this->table(['ID', 'Name', 'Email'], $users->map(fn ($u) => [$u->id, $u->name, $u->email]));

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $user->forceFill(['referral_code' => User::generateUniqueReferralCode()])->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Referral codes generated for {$users->count()} user(s).");

        return Command::SUCCESS;
    }
}
