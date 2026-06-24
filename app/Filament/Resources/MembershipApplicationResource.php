<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipApplicationResource\Pages;
use App\Models\MemberProfile;
use App\Models\UserProfile;
use App\Services\MembershipApprovalService;
use App\Services\MembershipIdService;
use App\Services\MembershipStateService;
use Carbon\Carbon;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MembershipApplicationResource extends Resource
{
    protected static ?string $model = MemberProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Approvals';

    protected static ?string $navigationLabel = 'Pending Members';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('onboarding_status', [
                'pending_review',
                'payment_pending',
                'payment_processing',
                'payment_failed',
                'needs_correction',
            ])->with('user'))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('onboarding_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_review' => 'warning',
                        'payment_pending' => 'info',
                        'payment_processing' => 'info',
                        'payment_failed' => 'danger',
                        'needs_correction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('location_country')
                    ->label('Location')
                    ->state(fn (MemberProfile $record): string => $record->location_country === 'Nigeria'
                        ? trim(($record->location_state ?? '').', Nigeria', ', ')
                        : ($record->location_international ?? '')),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('onboarding_status')
                    ->label('Status')
                    ->options([
                        'pending_review' => 'Pending Review',
                        'payment_pending' => 'Pending Payment',
                        'payment_processing' => 'Processing Payment',
                        'payment_failed' => 'Payment Failed',
                        'needs_correction' => 'Needs Correction',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('No pending applications')
            ->emptyStateDescription('All membership applications have been reviewed.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembershipApplications::route('/'),
            'view' => Pages\ViewMembershipApplication::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        $allowed = $user?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;

        if (! $allowed && $user) {
            Log::warning('Unauthorized Filament access attempt to MembershipApplicationResource', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return $allowed;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record = null): bool
    {
        return false;
    }

    public static function canDelete($record = null): bool
    {
        return false;
    }

    public static function approve(MemberProfile|UserProfile $profile, ?string $membershipType = 'M'): void
    {
        if ($profile instanceof UserProfile) {
            $generated = MembershipIdService::generate($membershipType ?? 'M');

            $profile->update([
                'membership_status' => 'payment_pending',
                'membership_type' => $generated['membership_type'],
                'membership_id' => $generated['membership_id'],
                'membership_serial' => $generated['membership_serial'],
                'membership_hijri_year' => $generated['membership_hijri_year'],
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            return;
        }

        app(MembershipApprovalService::class)->approve($profile, $membershipType ?? 'M');
    }

    public static function reject(MemberProfile|UserProfile $profile, string $reason): void
    {
        if ($profile instanceof UserProfile) {
            $profile->update(['membership_status' => 'rejected']);

            return;
        }

        app(MembershipApprovalService::class)->reject($profile, $reason);
    }

    public static function needsCorrection(MemberProfile|UserProfile $profile, string $notes): void
    {
        if ($profile instanceof UserProfile) {
            return;
        }

        app(MembershipApprovalService::class)->needsCorrection($profile, $notes);
    }

    public static function markPaymentFailed(MemberProfile|UserProfile $profile, ?string $reason = null): void
    {
        if ($profile instanceof UserProfile) {
            return;
        }

        $actor = auth()->user();

        app(MembershipStateService::class)->markFailed($profile, $actor);

        if ($reason) {
            $profile->forceFill(['payment_failed_reason' => $reason])->saveQuietly();
        }
    }

    public static function confirmPayment(MemberProfile|UserProfile $profile): void
    {
        if ($profile instanceof UserProfile) {
            $profile->update([
                'membership_status' => 'active',
                'payment_status' => 'paid',
                'membership_fee_paid_at' => now(),
            ]);

            $profile->user?->forceFill(['status' => 'active'])->saveQuietly();

            return;
        }

        app(MembershipApprovalService::class)->confirmPayment($profile);
    }
}
