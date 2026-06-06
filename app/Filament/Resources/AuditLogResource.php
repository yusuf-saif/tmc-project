<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Governance';

    protected static ?string $navigationLabel = 'Audit Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Actor')
                    ->state(fn (AuditLog $record): string => $record->user?->name ?? 'System'),
                Tables\Columns\TextColumn::make('action')->searchable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Target Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? Str::afterLast(str_replace('\\', '/', $state), '/') : 'System'),
                Tables\Columns\TextColumn::make('auditable_id')->label('Target ID'),
                Tables\Columns\TextColumn::make('ip_address'),
                Tables\Columns\TextColumn::make('created_at')->label('When')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Filter::make('action')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('action'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return filled($data['action'] ?? null)
                            ? $query->where('action', 'like', '%'.$data['action'].'%')
                            : $query;
                    }),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
