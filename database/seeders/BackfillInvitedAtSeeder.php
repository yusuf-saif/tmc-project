<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class BackfillInvitedAtSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('app/invited_backfill.txt');

        if (! file_exists($path)) {
            $this->command?->error('File not found: storage/app/invited_backfill.txt');

            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $emails = array_map('strtolower', array_map('trim', $lines));
        $emails = array_filter($emails);
        $emailCount = count($emails);

        $this->command?->info("Read {$emailCount} email(s) from invited_backfill.txt.");

        if ($emailCount === 0) {
            $this->command?->info('No emails to process.');

            return;
        }

        $placeholders = implode(', ', array_fill(0, $emailCount, 'LOWER(?)'));
        $updated = User::whereRaw("LOWER(email) IN ({$placeholders})", $emails)
            ->whereNull('invited_at')
            ->update(['invited_at' => now()]);

        $matchedEmails = User::whereIn('email', $emails)
            ->pluck('email')
            ->map('strtolower')
            ->all();

        $unmatched = array_diff($emails, $matchedEmails);

        $this->command?->info("Updated {$updated} user(s) with invited_at.");
        $this->command?->info('Matched '.count($matchedEmails).' email(s) to existing users.');

        if (count($unmatched) > 0) {
            $this->command?->warn('Emails with no matching user:');
            foreach ($unmatched as $email) {
                $this->command?->line("  - {$email}");
            }
        }

        $this->command?->warn('Reminder: delete storage/app/invited_backfill.txt after verifying — it contains member PII.');
    }
}
