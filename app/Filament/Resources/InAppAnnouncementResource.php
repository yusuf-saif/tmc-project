<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InAppAnnouncementResource\Pages;
use App\Models\InAppAnnouncement;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InAppAnnouncementResource extends Resource
{
    protected static ?string $model = InAppAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'In-App Announcements';

    protected static ?string $modelLabel = 'In-App Announcement';

    protected static ?string $pluralModelLabel = 'In-App Announcements';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')
                        ->required()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->options([
                            'info' => 'Info',
                            'warning' => 'Warning',
                            'success' => 'Success',
                        ])
                        ->required()
                        ->default('info'),
                    Forms\Components\Select::make('priority')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                        ])
                        ->required()
                        ->default('medium'),
                    Forms\Components\Toggle::make('dismissible')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Schedule')
                ->schema([
                    Forms\Components\DateTimePicker::make('start_at')
                        ->label('Starts At')
                        ->nullable(),
                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Expires At')
                        ->nullable()
                        ->afterOrEqual('start_at'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'info' => 'info',
                        'warning' => 'warning',
                        'success' => 'success',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                    }),
                Tables\Columns\IconColumn::make('dismissible')
                    ->boolean(),
                Tables\Columns\TextColumn::make('start_at')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('Immediately'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('Never'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No in-app announcements')
            ->emptyStateDescription('No in-app announcements have been created.')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resend Announcement')
                    ->modalDescription('This will create a copy of this announcement. You can then set new dates and activate it.')
                    ->action(function (InAppAnnouncement $record) {
                        $clone = $record->replicate();
                        $clone->status = 'inactive';
                        $clone->start_at = null;
                        $clone->expires_at = null;
                        $clone->created_by = auth()->id();
                        $clone->updated_by = auth()->id();
                        $clone->save();

                        AuditLogService::log('in_app_announcement_created', $clone, [], $clone->only([
                            'title', 'type', 'priority', 'status',
                        ]));

                        Notification::make()
                            ->title('Announcement duplicated')
                            ->success()
                            ->send();

                        $this->redirect(InAppAnnouncementResource::getUrl('edit', ['record' => $clone]));
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInAppAnnouncements::route('/'),
            'create' => Pages\CreateInAppAnnouncement::route('/create'),
            'edit' => Pages\EditInAppAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;
    }
}
