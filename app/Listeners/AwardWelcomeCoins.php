<?php

namespace App\Listeners;

use App\Events\MemberOnboardingCompleted;
use App\Events\MembershipActivated;
use App\Models\JannahCoinsLedger;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\CoinsService;
use Illuminate\Support\Facades\Log;

class AwardWelcomeCoins
{
    public function handle(MembershipActivated|MemberOnboardingCompleted $event): void
    {
        if (JannahCoinsLedger::query()
            ->where('user_id', $event->user->id)
            ->where('reason', 'welcome')
            ->exists()
        ) {
            Log::info('Welcome coins already awarded, skipping', ['user_id' => $event->user->id]);

            return;
        }

        $amount = (int) Setting::get('starter_coins_amount');

        if ($amount <= 0) {
            return;
        }

        try {
            CoinsService::award($event->user, $amount, 'welcome', null, 'Welcome bonus on membership activation');

            AuditLogService::log(
                'welcome_coins_awarded',
                null,
                [],
                [
                    'user_id' => $event->user->id,
                    'amount' => $amount,
                ],
            );

            Log::info('Welcome coins awarded', [
                'user_id' => $event->user->id,
                'amount' => $amount,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to award welcome coins', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
