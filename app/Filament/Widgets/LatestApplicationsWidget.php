<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SupportApplicationResource;
use App\Models\SupportApplication;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestApplicationsWidget extends TableWidget
{
    protected static ?string $heading = 'Latest Support Applications';

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportApplication::query()->latest('created_at')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'volunteer' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y') : '—'),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('View all')
                    ->url(SupportApplicationResource::getUrl('index')),
            ]);
    }
}
