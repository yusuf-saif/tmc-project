<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $old, ?string $operation): void {
                    if ($operation === 'create' && filled($state)) {
                        $set('slug', Str::slug($state));
                    }
                }),
            Forms\Components\TextInput::make('slug')->readOnly()->dehydrated(),
            Forms\Components\RichEditor::make('body')->required()->columnSpanFull(),
            Forms\Components\Select::make('status')->options([
                'draft' => 'Draft',
                'scheduled' => 'Scheduled',
                'published' => 'Published',
                'archived' => 'Archived',
            ])->required()->default('draft'),
            Forms\Components\DateTimePicker::make('publish_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('publish_at')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('published_at')->dateTime('d M Y H:i'),
            ])
            ->emptyStateHeading('No announcements')
            ->emptyStateDescription('No announcements have been published.')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'content_editor']) ?? false;
    }

    public static function logCreate(Announcement $record): void
    {
        AuditLogService::log('announcement_created', $record, [], $record->only(['title', 'status']));
    }

    public static function logUpdate(Announcement $record, array $old): void
    {
        AuditLogService::log('announcement_updated', $record, $old, $record->only(['title', 'status']));
    }
}
