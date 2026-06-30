<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommunitySpaceResource\Pages;
use App\Models\CommunitySpace;
use App\Services\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommunitySpaceResource extends Resource
{
    protected static ?string $model = CommunitySpace::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Community Spaces';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->readOnly()->dehydrated(),
            Forms\Components\TextInput::make('short_description')->required()->maxLength(255),
            Forms\Components\RichEditor::make('description')->required()->columnSpanFull(),
            Forms\Components\RichEditor::make('guidelines')->columnSpanFull(),
            Forms\Components\FileUpload::make("cover_image_path")
                ->image()
                ->imageResizeMode("cover")
                ->imageResizeTargetWidth(1200)
                ->imageResizeUpscale(false)
                ->disk("public")
                ->directory("community/covers")
                ->image(),
            Forms\Components\TextInput::make('external_link')->url(),
            Forms\Components\Toggle::make('is_youth_space'),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\IconColumn::make('is_youth_space')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->emptyStateHeading('No community spaces')
            ->emptyStateDescription('No community spaces have been created.')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommunitySpaces::route('/'),
            'create' => Pages\CreateCommunitySpace::route('/create'),
            'edit' => Pages\EditCommunitySpace::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'content_editor']) ?? false;
    }

    public static function logCreate(CommunitySpace $record): void
    {
        AuditLogService::log('community_space_created', $record, [], $record->only(['name', 'is_active', 'is_youth_space', 'sort_order']));
    }

    public static function logUpdate(CommunitySpace $record, array $old): void
    {
        AuditLogService::log('community_space_updated', $record, $old, $record->only(['name', 'is_active', 'is_youth_space', 'sort_order']));
    }
}
