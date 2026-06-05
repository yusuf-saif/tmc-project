<?php

namespace App\Services;

use App\Models\JannahCoinsLedger;
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

    public static function getHistory(User $user): LengthAwarePaginator
    {
        return JannahCoinsLedger::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);
    }
}
