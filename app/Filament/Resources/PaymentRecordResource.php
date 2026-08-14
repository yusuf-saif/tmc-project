<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentRecordResource\Pages;
use App\Models\PaymentRecord;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class PaymentRecordResource extends Resource
{
    protected static ?string $model = PaymentRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationGroup = 'Approvals';

    protected static ?string $navigationLabel = 'Payment Records';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->state(fn (PaymentRecord $record): string => $record->user?->name ?? '—'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->state(fn (PaymentRecord $record): string => $record->user?->email ?? '—'),
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paystack' => 'Paystack',
                        'manual' => 'Bank Transfer',
                        default => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'paystack' => 'success',
                        'manual' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? '—')),
                Tables\Columns\TextColumn::make('amount_kobo')
                    ->label('Amount')
                    ->formatStateUsing(fn (?int $state): string => $state ? '₦'.number_format($state / 100) : '—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('provider')
                    ->options([
                        'paystack' => 'Paystack',
                        'manual' => 'Bank Transfer',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (?PaymentRecord $record): bool => $record?->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel payment record')
                    ->modalDescription('Mark this abandoned pending payment record as cancelled. This does not affect the member\'s profile status.')
                    ->action(function (PaymentRecord $record): void {
                        $record->forceFill([
                            'status' => 'cancelled',
                            'failure_reason' => 'Cancelled by admin',
                        ])->saveQuietly();

                        AuditLogService::log(
                            action: 'manual_payment_cancelled',
                            model: $record,
                            old: ['status' => 'pending'],
                            new: ['status' => 'cancelled'],
                            targetUserId: $record->user_id,
                        );

                        Notification::make()
                            ->title('Payment record cancelled')
                            ->success()
                            ->send();

                        Log::info('PaymentRecordResource: pending record cancelled', [
                            'record_id' => $record->id,
                            'user_id' => $record->user_id,
                            'actor_id' => auth()->id(),
                        ]);
                    }),
            ])
            ->emptyStateHeading('No payment records')
            ->emptyStateDescription('No payment records have been created yet.')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentRecords::route('/'),
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
