<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CoinsService
{
    public static function getBalance(User $user): int
    {
        if (! Schema::hasTable('jannah_coins_ledger')) {
            return 0;
        }

        return (int) DB::table('jannah_coins_ledger')
            ->where('user_id', $user->id)
            ->sum('amount');
    }
}
