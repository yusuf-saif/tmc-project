<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Events';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old, ?string $operation): void {
                        if ($operation === 'create' && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    }),
                Forms\Components\TextInput::make('slug')
                    ->readOnly()
                    ->dehydrated()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('location_type')
                    ->options([
                        'online' => 'Online',
                        'in_person' => 'In Person',
                        'hybrid' => 'Hybrid',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('location_detail')
                    ->placeholder('Zoom link, address, etc.'),
                Forms\Components\DateTimePicker::make('event_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date'),
                Forms\Components\FileUpload::make('cover_image_path')
                    ->image()
                    ->disk('public')
                    ->directory('events/covers')
                    ->maxSize(2048),
                Forms\Components\TextInput::make('external_link')
                    ->url(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ])
                    ->required()
                    ->default('draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount(['rsvps as active_rsvps_count' => fn ($rsvpQuery) => $rsvpQuery->active()]))
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_path')
                    ->disk('public')
                    ->size(30),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        'cancelled' => 'danger',
                        'completed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('event_date')
                    ->dateTime('D M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_rsvps_count')
                    ->label('RSVPs'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('rsvps')
                    ->label('RSVPs')
                    ->url(fn (Event $record): string => static::getUrl('rsvps', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->color('success')
                    ->visible(fn (Event $record): bool => $record->status === 'draft')
                    ->action(function (Event $record): void {
                        $record->update([
                            'status' => 'published',
                            'updated_by' => auth()->id(),
                        ]);

                        AuditLogService::log('event_published', $record, ['status' => 'draft'], ['status' => 'published']);
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->visible(fn (Event $record): bool => $record->status !== 'cancelled')
                    ->action(function (Event $record): void {
                        $old = $record->status;

                        $record->update([
                            'status' => 'cancelled',
                            'updated_by' => auth()->id(),
                        ]);

                        AuditLogService::log('event_cancelled', $record, ['status' => $old], ['status' => 'cancelled']);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'rsvps' => Pages\EventRsvpList::route('/{record}/rsvps'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'content_editor']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }
}
