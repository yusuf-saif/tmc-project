<?php

namespace App\Console\Commands;

use App\Models\MemberProfile;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecoverStalePayments extends Command
{
    protected $signature = 'membership:recover-stale-payments';

    protected $description = 'Transition stale payment_processing records (>60 min) to payment_failed';

    public function handle(): int
    {
        $cutoff = now()->subMinutes(60);

        $stale = MemberProfile::query()
            ->where('onboarding_status', 'payment_processing')
            ->where('payment_submitted_at', '<', $cutoff)
            ->orWhere(function ($q) use ($cutoff) {
                $q->where('onboarding_status', 'payment_processing')
                    ->whereNull('payment_submitted_at')
                    ->where('updated_at', '<', $cutoff);
            })
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale payment records found.');

            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($stale as $profile) {
            $profile->forceFill([
                'onboarding_status' => 'payment_failed',
                'payment_failed_reason' => 'Payment processing timed out after 60 minutes',
            ])->saveQuietly();

            AuditLogService::log(
                action: 'payment_stale_recovered',
                model: $profile,
                old: ['onboarding_status' => 'payment_processing'],
                new: ['onboarding_status' => 'payment_failed', 'reason' => 'Payment processing timed out after 60 minutes'],
                targetUserId: $profile->user_id,
            );

            Log::info('RecoverStalePayments: recovered stale payment', [
                'profile_id' => $profile->id,
                'user_id' => $profile->user_id,
            ]);

            $count++;
        }

        $this->info("Recovered {$count} stale payment record(s).");

        return Command::SUCCESS;
    }
}
