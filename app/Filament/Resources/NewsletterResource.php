<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterResource\Pages;
use App\Models\Goal;
use App\Models\Interest;
use App\Models\Newsletter;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Communications';

    protected static ?string $navigationLabel = 'Newsletters';

    protected static ?string $modelLabel = 'Newsletter';

    protected static ?string $pluralModelLabel = 'Newsletters';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'link', 'image',
                            'h2', 'h3', 'bulletList', 'orderedList',
                        ])
                        ->imageUpload(true)
                        ->imageUploadDisk('r2')
                        ->imageUploadDirectory('newsletters/images')
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
                    Forms\Components\DateTimePicker::make('schedule_at')
                        ->label('Schedule At')
                        ->nullable()
                        ->columnSpan(1),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')->searchable(),
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
                        'draft' => 'gray',
                        'scheduled' => 'info',
                        'sending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('sent_count')
                    ->label('Sent'),
                Tables\Columns\TextColumn::make('schedule_at')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('Not scheduled'),
                Tables\Columns\TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No newsletters')
            ->emptyStateDescription('No newsletters have been created.')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('send')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Send Newsletter Now')
                    ->modalDescription('This will immediately queue the newsletter for delivery to all targeted recipients.')
                    ->visible(fn (Newsletter $record) => in_array($record->status, ['draft', 'scheduled']))
                    ->action(function (Newsletter $record) {
                        app(NotificationService::class)->queueNewsletter($record);
                        AuditLogService::log('newsletter_dispatched', $record, [], ['subject' => $record->subject]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Newsletter $record) => in_array($record->status, ['draft', 'failed'])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletters::route('/'),
            'create' => Pages\CreateNewsletter::route('/create'),
            'edit' => Pages\EditNewsletter::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;
    }
}
