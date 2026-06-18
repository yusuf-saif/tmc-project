<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BroadcastResource\Pages;
use App\Jobs\SendBroadcastNotificationJob;
use App\Models\Broadcast;
use App\Models\Goal;
use App\Models\Interest;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BroadcastResource extends Resource
{
    protected static ?string $model = Broadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'Push Broadcasts';

    protected static ?string $modelLabel = 'Broadcast';

    protected static ?string $pluralModelLabel = 'Broadcasts';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('body')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Targeting & Schedule')
                ->schema([
                    Forms\Components\Select::make('target_audience')
                        ->label('Target Audience')
                        ->options([
                            'all' => 'All Users',
                            'members' => 'Members Only',
                            'exco' => 'Exco Only',
                            'interest' => 'By Interest',
                            'goal' => 'By Goal',
                        ])
                        ->required()
                        ->default('all')
                        ->live()
                        ->columnSpan(1),
                    Forms\Components\Select::make('audience_value')
                        ->label('Select Interests/Goals')
                        ->options(function (Forms\Get $get) {
                            $type = $get('target_audience');
                            if ($type === 'interest') {
                                return Interest::query()->active()->pluck('name', 'id')->all();
                            }
                            if ($type === 'goal') {
                                return Goal::query()->active()->pluck('name', 'id')->all();
                            }

                            return [];
                        })
                        ->multiple()
                        ->visible(fn (Forms\Get $get) => in_array($get('target_audience'), ['interest', 'goal']))
                        ->columnSpan(1),
                    Forms\Components\DateTimePicker::make('send_at')
                        ->label('Send At (leave empty for immediate)')
                        ->nullable()
                        ->columnSpan(1),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At (do not deliver after)')
                        ->nullable()
                        ->columnSpan(1),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('target_audience')
                    ->label('Audience')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all' => 'info',
                        'members' => 'success',
                        'exco' => 'warning',
                        'interest' => 'primary',
                        'goal' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'info',
                        'sending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('delivery_count')
                    ->label('Delivered'),
                Tables\Columns\TextColumn::make('send_at')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Immediate'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Broadcast Now')
                    ->modalDescription('This will queue the broadcast for immediate delivery.')
                    ->visible(fn (Broadcast $record) => $record->status === 'queued')
                    ->action(function (Broadcast $record) {
                        $record->update(['send_at' => now()]);
                        SendBroadcastNotificationJob::dispatch($record);
                        AuditLogService::log('broadcast_dispatched', $record, [], ['title' => $record->title]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Broadcast $record) => in_array($record->status, ['queued', 'failed'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBroadcasts::route('/'),
            'create' => Pages\CreateBroadcast::route('/create'),
            'edit' => Pages\EditBroadcast::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }
}
