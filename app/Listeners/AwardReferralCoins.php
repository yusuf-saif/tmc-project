<?php

namespace App\Listeners;

use App\Events\MembershipActivated;
use App\Models\JannahCoinsLedger;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class AwardReferralCoins
{
    public function handle(MembershipActivated $event): void
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

            if (! $referral->wasRecentlyCreated && $referral->coins_awarded) {
                return;
            }

            if (! $referral->wasRecentlyCreated && ! $referral->coins_awarded) {
                $referral->update(['coins_awarded' => true]);
            }

            $amount = (int) Setting::get('referral_coins_amount');

            JannahCoinsLedger::query()->create([
                'user_id' => $referrer->id,
                'type' => 'earned',
                'reason' => 'referral',
                'amount' => $amount,
                'reference_id' => $user->id,
            ]);

            AuditLogService::log('referral_coins_awarded', $referral, [], [
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'amount' => $amount,
            ]);
        });
    }
}
