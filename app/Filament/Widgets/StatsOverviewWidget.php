<?php

namespace App\Filament\Widgets;

use App\Models\Broadcast;
use App\Models\Event;
use App\Models\InAppAnnouncement;
use App\Models\JannahCoinsLedger;
use App\Models\MemberProfile;
use App\Models\Newsletter;
use App\Models\SouqListing;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function statsData(): array
    {
        $memberIds = User::role('member')->pluck('id');
        $volunteerIds = User::role('volunteer')->pluck('id');

        $data = [
            'total_users' => User::query()->count(),
            'total_members' => User::query()->whereIn('id', $memberIds->merge($volunteerIds)->unique())->count(),
            'active_last_30_days' => User::query()
                ->where('updated_at', '>=', now()->subDays(30))
                ->whereHas('profile', fn ($query) => $query->whereNotNull('onboarding_completed_at'))
                ->count(),
            'pending_approvals' => MemberProfile::query()->where('onboarding_status', 'pending_review')->count(),
            'pending_souq' => SouqListing::query()->pending()->count(),
            'upcoming_events' => Event::query()->published()->upcoming()->count(),
            'coins_awarded' => (int) JannahCoinsLedger::query()->where('type', 'earned')->sum('amount'),
        ];

        if (Schema::hasTable('in_app_announcements')) {
            $data['active_announcements'] = InAppAnnouncement::query()->where('status', 'active')->count();
        } else {
            $data['active_announcements'] = 0;
        }

        if (Schema::hasTable('broadcasts')) {
            $data['broadcasts_sent'] = Broadcast::query()->where('status', 'sent')->count();
        } else {
            $data['broadcasts_sent'] = 0;
        }

        if (Schema::hasTable('newsletters')) {
            $data['newsletters_sent'] = Newsletter::query()->where('status', 'sent')->count();
        } else {
            $data['newsletters_sent'] = 0;
        }

        return $data;
    }

    protected function getStats(): array
    {
        $stats = static::statsData();

        return [
            Stat::make('Total Users', $stats['total_users'])
                ->description('All registered users')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Active Members', $stats['active_last_30_days'])
                ->description('Active in last 30 days')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),
            Stat::make('Pending Approvals', $stats['pending_approvals'])
                ->description('Awaiting review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Jannah Coins Awarded', number_format($stats['coins_awarded']))
                ->description('Total earned')
                ->descriptionIcon('heroicon-o-star')
                ->color('warning'),
            Stat::make('Upcoming Events', $stats['upcoming_events'])
                ->description('Published events')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Active Announcements', $stats['active_announcements'])
                ->description('In-app announcements')
                ->descriptionIcon('heroicon-o-megaphone')
                ->color('primary'),
            Stat::make('Broadcasts Sent', $stats['broadcasts_sent'])
                ->description('Push notifications')
                ->descriptionIcon('heroicon-o-signal')
                ->color('success'),
            Stat::make('Newsletters Sent', $stats['newsletters_sent'])
                ->description('Email campaigns')
                ->descriptionIcon('heroicon-o-envelope')
                ->color('info'),
        ];
    }
}
