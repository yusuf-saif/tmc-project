<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipApplicationResource\Pages;
use App\Models\MemberProfile;
use App\Models\UserProfile;
use App\Services\MembershipApprovalService;
use App\Services\MembershipIdService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MembershipApplicationResource extends Resource
{
    protected static ?string $model = MemberProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Members';

    protected static ?string $navigationLabel = 'Pending Members';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('onboarding_status', 'pending_review')->with('user'))
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location_country')
                    ->label('Location')
                    ->state(fn (MemberProfile $record): string => $record->location_country === 'Nigeria'
                        ? trim(($record->location_state ?? '').', Nigeria', ', ')
                        : ($record->location_international ?? '')),
                Tables\Columns\TextColumn::make('age_group')
                    ->label('Age Group')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'under_18' => 'Under 18',
                        '18_24' => '18 - 24',
                        '25_34' => '25 - 34',
                        '35_44' => '35 - 44',
                        '45_54' => '45 - 54',
                        '55_above' => '55+',
                        default => $state ?? 'N/A',
                    }),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function approve(MemberProfile|UserProfile $profile, ?string $membershipType = 'M'): void
    {
        if ($profile instanceof UserProfile) {
            $generated = MembershipIdService::generate($membershipType ?? 'M');

            $profile->update([
                'membership_status' => 'approved_pending_payment',
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
}
