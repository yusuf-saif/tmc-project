<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResourceResource\Pages;
use App\Models\Resource as LibraryResource;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ResourceResource extends Resource
{
    protected static ?string $model = LibraryResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Resources';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Forms\Set $set, ?string $state, ?string $operation): void {
                    if ($operation === 'create' && filled($state)) {
                        $set('slug', Str::slug($state));
                    }
                }),
            Forms\Components\TextInput::make('slug')
                ->readOnly()
                ->dehydrated(),
            Forms\Components\Textarea::make('description')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\Select::make('category')
                ->options([
                    'dua_book' => "Du'a Book",
                    'dear_allah' => 'Dear Allah',
                    'pocket_guide' => 'Pocket Guide',
                    'audio_halaqahs' => 'Audio & Halaqahs',
                ])
                ->required(),
            Forms\Components\Select::make('type')
                ->options([
                    'article' => 'Article',
                    'dua' => 'Dua',
                    'pdf' => 'PDF',
                    'audio' => 'Audio',
                    'video_link' => 'Video Link',
                    'guide' => 'Guide',
                ])
                ->required(),
            Forms\Components\RichEditor::make('body')
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('file_path')
                ->disk('public')
                ->directory('resources/files')
                ->acceptedFileTypes(['image/*', 'application/pdf', 'audio/*'])
                ->maxSize(10240),
            Forms\Components\TextInput::make('external_url')
                ->url(),
            Forms\Components\FileUpload::make('thumbnail_path')
                ->disk('public')
                ->directory('resources/thumbnails')
                ->image(),
            Forms\Components\Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'archived' => 'Archived',
                ])
                ->default('draft')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'gray',
                        'archived' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'dua_book' => "Du'a Book",
                        'dear_allah' => 'Dear Allah',
                        'pocket_guide' => 'Pocket Guide',
                        'audio_halaqahs' => 'Audio & Halaqahs',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
            ])
            ->emptyStateHeading('No resources')
            ->emptyStateDescription('No library resources have been created.')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (LibraryResource $record): void {
                        AuditLogService::log('resource_deleted', $record, $record->only(['title', 'status', 'category', 'type']), []);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResources::route('/'),
            'create' => Pages\CreateResource::route('/create'),
            'view' => Pages\ViewResource::route('/{record}'),
            'edit' => Pages\EditResource::route('/{record}/edit'),
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

    public static function canDelete($record): bool
    {
        return static::canAccess();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function logCreate(LibraryResource $resource): void
    {
        AuditLogService::log('resource_created', $resource, [], $resource->only(['title', 'status', 'category', 'type']));
    }

    public static function logUpdate(LibraryResource $resource, array $old): void
    {
        AuditLogService::log('resource_updated', $resource, $old, $resource->only(['title', 'status', 'category', 'type']));
    }
}
