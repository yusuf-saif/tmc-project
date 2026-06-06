<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

class RecentActivityWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Admin Activity';

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with('user')->latest()->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Actor')
                    ->state(fn (AuditLog $record): string => $record->user?->name ?? 'System'),
                Tables\Columns\TextColumn::make('action'),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Target')
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::afterLast(str_replace('\\', '/', $state), '/') : 'System'),
                Tables\Columns\TextColumn::make('ip_address'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('d M Y H:i'),
            ])
            ->paginated(false);
    }
}
