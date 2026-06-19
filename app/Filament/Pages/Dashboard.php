<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestApplicationsWidget;
use App\Filament\Widgets\PendingApplicationsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\StatsOverviewWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?int $navigationSort = -2;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            PendingApplicationsWidget::class,
            LatestApplicationsWidget::class,
            RecentActivityWidget::class,
        ];
    }
}
