<?php

namespace App\Listeners;

use App\Models\JannahCoinsLedger;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class AwardReferralCoins
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if (! $user instanceof User || ! $user->referred_by) {
            return;
        }

        $referrer = User::query()->find($user->referred_by);

        if (! $referrer) {
            return;
        }

        DB::transaction(function () use ($referrer, $user): void {
            $referral = UserReferral::query()->firstOrCreate(
                ['referred_id' => $user->id],
                [
                    'referrer_id' => $referrer->id,
                    'coins_awarded' => true,
                ],
            );

            if (! $referral->wasRecentlyCreated) {
                return;
            }

            JannahCoinsLedger::query()->create([
                'user_id' => $referrer->id,
                'type' => 'earned',
                'reason' => 'referral',
                'amount' => 25,
                'reference_id' => $user->id,
            ]);

            AuditLogService::log('referral_coins_awarded', $referral, [], [
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'amount' => 25,
            ]);
        });
    }
}
