<?php

namespace App\Services;

use App\Models\JannahCoinsLedger;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CoinsService
{
    public static function getBalance(User $user): int
    {
        return (int) JannahCoinsLedger::query()
            ->where('user_id', $user->id)
            ->sum('amount');
    }

    public static function award(
        User $user,
        int $amount,
        string $reason,
        ?int $referenceId = null,
        ?string $adminNote = null,
    ): void {
        JannahCoinsLedger::query()->create([
            'user_id' => $user->id,
            'type' => 'earned',
            'reason' => $reason,
            'amount' => $amount,
            'reference_id' => $referenceId,
            'admin_note' => $adminNote,
        ]);
    }

    public static function deduct(
        User $user,
        int $amount,
        string $reason,
        string $adminNote,
    ): void {
        JannahCoinsLedger::query()->create([
            'user_id' => $user->id,
            'type' => 'deducted',
            'reason' => $reason,
            'amount' => -abs($amount),
            'admin_note' => $adminNote,
        ]);
    }

    public static function coinValueKobo(): int
    {
        return (int) Setting::get('coin_value_kobo');
    }

    public static function maxRedemptionPercent(): int
    {
        return (int) Setting::get('max_redemption_percent');
    }

    public static function calculateMaxDiscount(User $user, int $paymentAmountKobo): array
    {
        $balance = self::getBalance($user);
        $coinValue = self::coinValueKobo();
        $maxPercent = self::maxRedemptionPercent();

        $fullCoinValueKobo = $balance * $coinValue;
        $maxAllowedByPercent = (int) floor($paymentAmountKobo * ($maxPercent / 100));

        $discountKobo = min($fullCoinValueKobo, $maxAllowedByPercent);
        $coinsToUse = $coinValue > 0 ? (int) floor($discountKobo / $coinValue) : 0;
        $actualDiscountKobo = $coinsToUse * $coinValue;

        return [
            'eligible' => $coinsToUse > 0,
            'coins_to_use' => $coinsToUse,
            'discount_kobo' => $actualDiscountKobo,
            'final_amount_kobo' => max(0, $paymentAmountKobo - $actualDiscountKobo),
        ];
    }

    public static function applyRedemption(User $user, int $coinsUsed, string $context, int $referenceId): void
    {
        JannahCoinsLedger::create([
            'user_id' => $user->id,
            'type' => 'deducted',
            'amount' => -$coinsUsed,
            'reason' => 'redemption_'.$context,
            'reference_id' => $referenceId,
        ]);
    }

    public static function getHistory(User $user): LengthAwarePaginator
    {
        return JannahCoinsLedger::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);
    }
}
