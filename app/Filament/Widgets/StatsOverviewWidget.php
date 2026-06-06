<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\JannahCoinsLedger;
use App\Models\SouqListing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    public static function statsData(): array
    {
        $memberIds = User::role('member')->pluck('id');
        $volunteerIds = User::role('volunteer')->pluck('id');

        return [
            'total_members' => User::query()->whereIn('id', $memberIds->merge($volunteerIds)->unique())->count(),
            'active_last_30_days' => User::query()
                ->where('updated_at', '>=', now()->subDays(30))
                ->whereHas('profile', fn ($query) => $query->whereNotNull('onboarding_completed_at'))
                ->count(),
            'pending_souq' => SouqListing::query()->pending()->count(),
            'upcoming_events' => Event::query()->published()->upcoming()->count(),
            'coins_awarded' => (int) JannahCoinsLedger::query()->where('type', 'earned')->sum('amount'),
        ];
    }

    protected function getStats(): array
    {
        $stats = static::statsData();

        return [
            Stat::make('Total Members', $stats['total_members'])
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Active (30 days)', $stats['active_last_30_days'])
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),
            Stat::make('Pending Souq', $stats['pending_souq'])
                ->icon('heroicon-o-shopping-bag')
                ->color('warning'),
            Stat::make('Upcoming Events', $stats['upcoming_events'])
                ->icon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Jannah Coins Awarded', number_format($stats['coins_awarded']))
                ->icon('heroicon-o-star')
                ->color('warning'),
        ];
    }
}
