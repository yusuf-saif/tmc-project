<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LatestApplicationsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\StatsOverviewWidget;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            LatestApplicationsWidget::class,
            RecentActivityWidget::class,
        ];
    }
}
