<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('user_badges')
            ->select('user_id', 'badge_id')
            ->selectRaw('MIN(id) as keep_id')
            ->groupBy('user_id', 'badge_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table('user_badges')
                ->where('user_id', $duplicate->user_id)
                ->where('badge_id', $duplicate->badge_id)
                ->where('id', '!=', $duplicate->keep_id)
                ->get();

            foreach ($rows as $row) {
                Log::warning('Removed duplicate badge award before adding unique constraint', [
                    'user_id' => $row->user_id,
                    'badge_id' => $row->badge_id,
                    'removed_user_badge_id' => $row->id,
                    'kept_user_badge_id' => $duplicate->keep_id,
                ]);

                DB::table('user_badges')->where('id', $row->id)->delete();
            }
        }

        // NOTE: Coin balances are intentionally NOT adjusted for any duplicate
        // rows removed above. This is a deliberate, consistent policy decision:
        // for the one known production duplicate (user 15, badge 2) we chose not
        // to claw back the excess coins without explanation, judging that worse
        // than leaving a small one-time overage. Any duplicate discovered by this
        // migration is treated the same way — its coin balance is left untouched.

        Schema::table('user_badges', function (Blueprint $table) {
            $table->unique(['user_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'badge_id']);
        });
    }
};
