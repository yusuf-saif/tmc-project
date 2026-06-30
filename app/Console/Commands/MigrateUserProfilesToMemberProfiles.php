<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateUserProfilesToMemberProfiles extends Command
{
    protected $signature = 'membership:migrate-profiles';

    protected $description = 'Migrate data from user_profiles table to member_profiles table';

    public function handle(): int
    {
        if (! app()->runningInConsole()) {
            return self::SUCCESS;
        }

        $userProfiles = DB::table('user_profiles')->get();

        if ($userProfiles->isEmpty()) {
            $this->info('No user profiles to migrate.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($userProfiles->count());
        $bar->start();

        $migrated = 0;
        $created = 0;

        foreach ($userProfiles as $up) {
            $memberProfile = MemberProfile::where('user_id', $up->user_id)->first();

            if (! $memberProfile) {
                $memberProfile = MemberProfile::create([
                    'user_id' => $up->user_id,
                ]);
                $created++;
            }

            $memberProfile->forceFill([
                'display_name' => $up->display_name,
                'avatar_path' => $up->avatar_path,
                'notification_preferences' => is_string($up->notification_preferences) ? json_decode($up->notification_preferences, true) : $up->notification_preferences,
                'onboarding_completed_at' => $up->onboarding_completed_at,
                'membership_serial' => $up->membership_serial,
                'payment_status' => $up->payment_status,
            ])->save();

            $migrated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Migrated {$migrated} user profiles. Created {$created} new member profiles.");

        return self::SUCCESS;
    }
}
