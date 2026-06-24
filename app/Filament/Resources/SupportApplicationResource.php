<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportApplicationResource\Pages;
use App\Models\SupportApplication;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportApplicationResource extends Resource
{
    protected static ?string $model = SupportApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'Approvals';

    protected static ?string $navigationLabel = 'Support Applications';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->readOnly(),
            Forms\Components\TextInput::make('email')->readOnly(),
            Forms\Components\TextInput::make('type')->readOnly(),
            Forms\Components\Textarea::make('skills_or_focus')->readOnly()->columnSpanFull(),
            Forms\Components\Textarea::make('motivation')->readOnly()->columnSpanFull(),
            Forms\Components\TextInput::make('availability')->readOnly(),
            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending',
                'reviewed' => 'Reviewed',
                'accepted' => 'Accepted',
                'declined' => 'Declined',
            ]),
            Forms\Components\Textarea::make('admin_notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('created_at')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'volunteer' => 'Volunteer',
                    'mentorship' => 'Mentorship',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'reviewed' => 'Reviewed',
                    'accepted' => 'Accepted',
                    'declined' => 'Declined',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('review')
                    ->label('Mark Reviewed')
                    ->action(fn (SupportApplication $record) => static::updateStatus($record, 'reviewed', 'support_reviewed')),
                Tables\Actions\Action::make('accept')
                    ->color('success')
                    ->action(fn (SupportApplication $record) => static::updateStatus($record, 'accepted', 'support_accepted')),
                Tables\Actions\Action::make('decline')
                    ->color('danger')
                    ->action(fn (SupportApplication $record) => static::updateStatus($record, 'declined', 'support_declined')),
            ])
            ->emptyStateHeading('No support applications')
            ->emptyStateDescription('No volunteer or mentorship applications have been submitted yet.')
            ->emptyStateIcon('heroicon-o-hand-raised');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportApplications::route('/'),
            'edit' => Pages\EditSupportApplication::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function updateStatus(SupportApplication $record, string $status, string $action): void
    {
        $old = $record->status;
        $record->update([
            'status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        AuditLogService::log($action, $record, ['status' => $old], ['status' => $status]);
        Notification::make()->title('Application updated')->success()->send();
    }
}
