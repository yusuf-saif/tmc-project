<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MembershipApplicationResource\Pages;
use App\Models\MemberProfile;
use App\Services\MembershipApprovalService;
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

    protected static ?string $navigationLabel = 'Member Profiles';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
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
                        'registered' => 'gray',
                        'active' => 'success',
                        'member' => 'primary',
                        'suspended' => 'danger',
                        'onboarding' => 'warning',
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
                        'registered' => 'Registered',
                        'active' => 'Active',
                        'member' => 'Member',
                        'suspended' => 'Suspended',
                        'onboarding' => 'Legacy: Awaiting Payment',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->emptyStateHeading('No member profiles found')
            ->emptyStateDescription('Member profiles will appear here once users complete registration.')
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

    /** @deprecated This resource no longer manages approval actions. Keep for MembershipApprovalService backward compat. */
    public static function approve(MemberProfile $profile, ?string $membershipType = 'M'): void
    {
        app(MembershipApprovalService::class)->approve($profile, $membershipType ?? 'M');
    }

    /** @deprecated This resource no longer manages rejection actions. Keep for MembershipApprovalService backward compat. */
    public static function reject(MemberProfile $profile, string $reason): void
    {
        app(MembershipApprovalService::class)->reject($profile, $reason);
    }

    /** @deprecated This resource no longer manages correction actions. Keep for MembershipApprovalService backward compat. */
    public static function needsCorrection(MemberProfile $profile, string $notes): void
    {
        app(MembershipApprovalService::class)->needsCorrection($profile, $notes);
    }
}
