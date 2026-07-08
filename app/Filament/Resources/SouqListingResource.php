<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SouqListingResource\Pages;
use App\Models\SouqListing;
use App\Services\AuditLogService;
use App\Services\BusinessStateService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SouqListingResource extends Resource
{
    protected static ?string $model = SouqListing::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Commerce';

    protected static ?string $navigationLabel = 'Souq Listings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('business_name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('category')
                ->options(SouqListing::CATEGORY_OPTIONS)
                ->required(),
            Forms\Components\Textarea::make('description')
                ->required()
                ->maxLength(300)
                ->rows(5)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('contact_email')
                ->email()
                ->required(),
            Forms\Components\TextInput::make('phone'),
            Forms\Components\TextInput::make('website')
                ->url(),
            Forms\Components\TextInput::make('instagram'),
            Forms\Components\Select::make('status')
                ->options(SouqListing::STATUS_OPTIONS)
                ->required(),
            Forms\Components\Textarea::make('admin_note')
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('logo_path')
                ->image()
                ->imageResizeMode('cover')
                ->imageResizeTargetWidth(400)
                ->imageResizeUpscale(false)
                ->disk('r2')
                ->directory('souq/logos')
                ->image(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('owner')->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Member')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved_unpaid' => 'info',
                        'approved' => 'success',
                        'active' => 'success',
                        'rejected' => 'danger',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('payment_source')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, SouqListing $record): string => match (true) {
                        $record->payment_source === 'bank_transfer' && $record->status === 'approved_unpaid' => 'Bank Transfer',
                        $record->paystack_reference !== null && $record->status === 'approved_unpaid' => 'Paystack',
                        $record->status === 'active' && $record->payment_source === 'bank_transfer' => 'Bank Transfer',
                        $record->status === 'active' => 'Paystack',
                        default => '—',
                    })
                    ->color(fn (?string $state, SouqListing $record): string => match (true) {
                        $record->payment_source === 'bank_transfer' && $record->status === 'approved_unpaid' => 'warning',
                        $record->status === 'active' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Applied')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->default('pending')
                    ->options(SouqListing::STATUS_OPTIONS),
                Tables\Filters\SelectFilter::make('category')
                    ->options(SouqListing::CATEGORY_OPTIONS),
            ])
            ->emptyStateHeading('No souq listings')
            ->emptyStateDescription('No listings have been submitted yet.')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->visible(fn (SouqListing $record): bool => $record->status === 'pending')
                    ->action(function (SouqListing $record): void {
                        static::approveListing($record);

                        Notification::make()
                            ->title('Listing approved')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->visible(fn (SouqListing $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (SouqListing $record, array $data): void {
                        static::rejectListing($record, $data['admin_note']);

                        Notification::make()
                            ->title('Listing rejected')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('verifyBankPayment')
                    ->label('Verify Bank Payment')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (SouqListing $record): bool => $record->status === 'approved_unpaid' && $record->payment_source === 'bank_transfer')
                    ->requiresConfirmation()
                    ->modalHeading('Verify bank transfer payment')
                    ->modalDescription(fn (SouqListing $record): string => "Confirm that {$record->owner?->name} has paid ₦".number_format((int) $record->monthly_fee).' via bank transfer for this listing?')
                    ->modalSubmitActionLabel('Verify Payment')
                    ->action(function (SouqListing $record): void {
                        app(BusinessStateService::class)->activate($record);

                        $record->forceFill([
                            'payment_verified_by' => auth()->id(),
                            'payment_verified_at' => now(),
                        ])->saveQuietly();

                        Notification::make()
                            ->title('Payment verified — listing activated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('archive')
                    ->label('Archive')
                    ->color('gray')
                    ->visible(fn (SouqListing $record): bool => $record->status !== 'archived')
                    ->action(function (SouqListing $record): void {
                        static::archiveListing($record);

                        Notification::make()
                            ->title('Listing archived')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSouqListings::route('/'),
            'edit' => Pages\EditSouqListing::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canEdit($record): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function approveListing(SouqListing $record): void
    {
        app(BusinessStateService::class)->approve($record, null);
    }

    public static function rejectListing(SouqListing $record, string $adminNote): void
    {
        app(BusinessStateService::class)->reject($record, $adminNote);
    }

    public static function archiveListing(SouqListing $record): void
    {
        $old = $record->status;

        $record->update([
            'status' => 'archived',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLogService::log('souq_archived', $record, ['status' => $old], ['status' => 'archived']);
    }
}
