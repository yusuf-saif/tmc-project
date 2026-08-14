<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MembershipApplicationResource;
use App\Models\MemberProfile;
use App\Services\AuditLogService;
use App\Services\MembershipStateService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;

class ManagePayments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Approvals';

    protected static ?string $navigationLabel = 'Payment Approvals';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.manage-payments';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MemberProfile::query()
                    ->where(fn ($q) => $q
                        ->where('onboarding_status', 'onboarding')
                        ->orWhere('payment_status', 'pending_verification')
                    )
                    ->with('user')
            )
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('membership_id')
                    ->label('Membership ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'paystack' => 'Paystack',
                        default => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'bank_transfer' => 'warning',
                        'paystack' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('onboarding_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'onboarding' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state, MemberProfile $record): string => match (true) {
                        $record->payment_status === 'pending_verification' => 'Pending Verification',
                        $state === 'onboarding' => 'Awaiting Payment',
                        default => str($state)->replace('_', ' ')->title(),
                    }),
                Tables\Columns\TextColumn::make('payment_submitted_at')
                    ->label('Submitted')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payment_verified_at')
                    ->label('Verified')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending_verification' => 'Pending Verification',
                        'onboarding' => 'Awaiting Payment',
                    ])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'pending_verification' => $query->where('payment_status', 'pending_verification'),
                        'onboarding' => $query->where('onboarding_status', 'onboarding'),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Verify Payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MemberProfile $record): bool => $record->payment_status === 'pending_verification' && $record->payment_source === 'bank_transfer')
                    ->requiresConfirmation()
                    ->modalHeading('Verify bank transfer payment')
                    ->modalDescription(fn (MemberProfile $record): string => "Confirm that {$record->user?->name} has paid via bank transfer for ".ucfirst($record->preferred_billing_cycle ?? 'monthly').' billing?')
                    ->modalSubmitActionLabel('Verify Payment')
                    ->action(function (MemberProfile $record): void {
                        try {
                            $user = $record->user;
                            $planLabel = $record->preferred_billing_cycle ?? 'monthly';

                            $record->forceFill([
                                'payment_verified_by' => auth()->id(),
                            ])->saveQuietly();

                            $service = app(MembershipStateService::class);
                            $paymentRecord = $service->findOrCreateManualPaymentRecord($user, $record, $planLabel);

                            $service->recordPayment(
                                $record,
                                $user,
                                $paymentRecord->billing_cycle ?? $planLabel,
                                $paymentRecord,
                            );

                            $record->refresh();

                            AuditLogService::log(
                                action: 'manual_payment_verified',
                                model: $record,
                                old: ['payment_status' => 'pending_verification', 'onboarding_status' => $record->onboarding_status],
                                new: ['payment_status' => 'paid', 'onboarding_status' => $record->onboarding_status, 'membership_id' => $record->membership_id],
                                targetUserId: $user->id,
                            );

                            Notification::make()
                                ->title('Payment verified — membership activated')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Log::error('ManagePayments: verify failed', ['error' => $e->getMessage(), 'record_id' => $record->id]);
                            Notification::make()
                                ->title('Payment verification failed')
                                ->body('An error occurred. Please try again or contact support.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view')
                    ->label('View Application')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MemberProfile $record): string => MembershipApplicationResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('No pending payment approvals')
            ->emptyStateDescription('All payment records have been processed.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->bulkActions([]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }
}
