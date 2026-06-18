<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipApplicationResource\Pages;
use App\Models\UserProfile;
use App\Notifications\MembershipApproved;
use App\Notifications\MembershipNeedsCorrection;
use App\Notifications\MembershipPaymentConfirmed;
use App\Notifications\MembershipRejected;
use App\Services\AuditLogService;
use App\Services\MembershipIdService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembershipApplicationResource extends Resource
{
    protected static ?string $model = UserProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Members';

    protected static ?string $navigationLabel = 'Membership Applications';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->whereIn('membership_status', ['submitted', 'under_review', 'approved_pending_payment', 'payment_submitted', 'rejected', 'needs_correction'])
                ->with('user.roles')
            )
            ->defaultSort('application_submitted_at', 'desc')
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
                Tables\Columns\TextColumn::make('membership_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submitted' => 'warning',
                        'under_review' => 'info',
                        'approved_pending_payment' => 'success',
                        'payment_submitted' => 'info',
                        'active' => 'success',
                        'rejected' => 'danger',
                        'needs_correction' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                Tables\Columns\TextColumn::make('application_submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('membership_status')
                    ->label('Status')
                    ->options([
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'approved_pending_payment' => 'Approved – Pending Payment',
                        'payment_submitted' => 'Payment Submitted',
                        'active' => 'Active',
                        'rejected' => 'Rejected',
                        'needs_correction' => 'Needs Correction',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (UserProfile $record): string => MembershipApplicationResource::getUrl('view', ['record' => $record->id])),
            ])
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
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;
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

    public static function approve(UserProfile $profile): void
    {
        $user = $profile->user;
        $membershipType = MembershipIdService::determineMembershipType($user);
        $idData = MembershipIdService::generate($membershipType);

        $profile->update([
            'membership_id' => $idData['membership_id'],
            'membership_type' => $idData['membership_type'],
            'membership_serial' => $idData['membership_serial'],
            'membership_hijri_year' => $idData['membership_hijri_year'],
            'membership_status' => 'approved_pending_payment',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        AuditLogService::log('membership_approved', $profile, [
            'membership_status' => $profile->getOriginal('membership_status'),
        ], [
            'membership_status' => 'approved_pending_payment',
            'membership_id' => $idData['membership_id'],
        ]);

        $user->notify(new MembershipApproved($user, $idData['membership_id']));
    }

    public static function reject(UserProfile $profile, string $reason): void
    {
        $oldStatus = $profile->membership_status;
        $profile->update([
            'membership_status' => 'rejected',
        ]);

        AuditLogService::log('membership_rejected', $profile, [
            'membership_status' => $oldStatus,
        ], [
            'membership_status' => 'rejected',
            'reason' => $reason,
        ]);

        $profile->user->notify(new MembershipRejected($reason));
    }

    public static function requestCorrection(UserProfile $profile, string $notes): void
    {
        $oldStatus = $profile->membership_status;
        $profile->update([
            'membership_status' => 'needs_correction',
        ]);

        AuditLogService::log('membership_correction_requested', $profile, [
            'membership_status' => $oldStatus,
        ], [
            'membership_status' => 'needs_correction',
            'notes' => $notes,
        ]);

        $profile->user->notify(new MembershipNeedsCorrection($notes));
    }

    public static function confirmPayment(UserProfile $profile): void
    {
        $oldStatus = $profile->membership_status;
        $profile->update([
            'membership_status' => 'active',
            'payment_status' => 'paid',
            'membership_fee_paid_at' => now(),
        ]);

        AuditLogService::log('membership_payment_confirmed', $profile, [
            'membership_status' => $oldStatus,
        ], [
            'membership_status' => 'active',
            'payment_status' => 'paid',
        ]);

        $profile->user->notify(new MembershipPaymentConfirmed($profile->membership_id));
    }
}
