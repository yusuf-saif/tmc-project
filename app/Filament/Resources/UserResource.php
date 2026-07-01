<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserRoleHistory;
use App\Services\AuditLogService;
use App\Services\CoinsService;
use App\Services\MembershipIdService;
use Carbon\Carbon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Members';

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['roles', 'profile']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->formatStateUsing(fn ($state, User $record): string => $record->getRoleNames()->join(', ')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Member Since')
                    ->formatStateUsing(fn ($state, User $record): string => $record->created_at->hijri('M Y')),
                Tables\Columns\TextColumn::make('coins_balance')
                    ->label('Coins')
                    ->state(fn (User $record): string => CoinsService::getBalance($record).' coins'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'member' => 'Member',
                        'volunteer' => 'Volunteer',
                        'moderator' => 'Moderator',
                        'content_editor' => 'Content Editor',
                        'admin' => 'Admin',
                        'super_admin' => 'Super Admin',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return $value ? $query->role($value) : $query;
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->emptyStateHeading('No users found')
            ->emptyStateDescription('No users match your filters.')
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Profile')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                        TextEntry::make('created_at')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                        TextEntry::make('profile.display_name')->label('Display Name'),
                        TextEntry::make('profile.onboarding_completed_at')->label('Onboarding Completed')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                    ])->columns(2),
                Section::make('Coins')
                    ->schema([
                        TextEntry::make('coins_balance')
                            ->label('Current Balance')
                            ->state(fn (User $record): string => CoinsService::getBalance($record).' coins'),
                        RepeatableEntry::make('jannahCoinsLedger')
                            ->label('Last 5 Entries')
                            ->state(fn (User $record) => $record->jannahCoinsLedger()->latest('created_at')->limit(5)->get())
                            ->schema([
                                TextEntry::make('created_at')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                                TextEntry::make('reason'),
                                TextEntry::make('amount')
                                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger'),
                            ])->columns(3),
                    ]),
                Section::make('Activity')
                    ->schema([
                        TextEntry::make('event_rsvps_count')
                            ->label('RSVP Count')
                            ->state(fn (User $record): int => $record->eventRsvps()->count()),
                        TextEntry::make('journal_entries_count')
                            ->label('Journal Entries (count only)')
                            ->state(fn (User $record): int => $record->journalEntries()->count()),
                        TextEntry::make('souq_listings_count')
                            ->label('Souq Listings')
                            ->state(fn (User $record): int => $record->souqListings()->count()),
                    ])->columns(3),
                Section::make('Badges')
                    ->schema([
                        RepeatableEntry::make('userBadges')
                            ->label('User Badges')
                            ->state(fn (User $record) => $record->userBadges()->with('badge')->get())
                            ->schema([
                                TextEntry::make('badge.name')->label('Badge'),
                                TextEntry::make('awarded_at')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;
    }

    public static function canChangeRole(User $actor): bool
    {
        return $actor->hasRole('super_admin');
    }

    public static function suspend(User $record, string $reason): void
    {
        $record->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $reason,
        ]);

        AuditLogService::log('user_suspended', $record, ['status' => 'active'], ['status' => 'suspended', 'reason' => $reason]);
    }

    public static function reactivate(User $record): void
    {
        $record->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        AuditLogService::log('user_reactivated', $record, ['status' => 'suspended'], ['status' => 'active']);
    }

    public static function awardBadge(User $record, int $badgeId): void
    {
        UserBadge::query()->create([
            'user_id' => $record->id,
            'badge_id' => $badgeId,
            'awarded_at' => now(),
            'awarded_by' => auth()->id(),
        ]);

        $badge = Badge::find($badgeId);

        if ($badge && $badge->coin_reward > 0) {
            app(CoinsService::class)->award(
                $record,
                $badge->coin_reward,
                'badge_reward',
                $badge->id,
            );
        }

        AuditLogService::log('badge_awarded', $record, [], ['badge_id' => $badgeId]);
    }

    public static function changeRole(User $record, string $newRole, ?string $reason = null): void
    {
        $oldRole = $record->getRoleNames()->first() ?? 'none';

        $record->syncRoles(['member', $newRole]);

        UserRoleHistory::query()->create([
            'user_id' => $record->id,
            'changed_by' => auth()->id(),
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'reason' => $reason,
        ]);

        AuditLogService::log('role_changed', $record, ['role' => $oldRole], ['role' => $newRole]);
    }

    public static function awardCoins(User $record, int $amount, string $reason): void
    {
        CoinsService::award($record, $amount, 'manual', null, $reason);
        AuditLogService::log('coins_awarded', $record, [], ['amount' => $amount, 'reason' => $reason]);
    }

    public static function deductCoins(User $record, int $amount, string $reason): void
    {
        CoinsService::deduct($record, $amount, 'manual', $reason);
        AuditLogService::log('coins_deducted', $record, [], ['amount' => $amount, 'reason' => $reason]);
    }

    public static function changeMembershipType(User $record, string $newType): void
    {
        $profile = $record->memberProfile;

        if (! $profile) {
            return;
        }

        $newType = MembershipIdService::normalizeType($newType);
        $oldType = $profile->membership_type ?? 'M';
        $oldMembershipId = $profile->membership_id;

        $idData = MembershipIdService::generate($newType);

        $profile->forceFill([
            'membership_type' => $newType,
            'membership_id' => $idData['membership_id'],
        ])->save();

        AuditLogService::log(
            'membership_type_changed',
            $record,
            [
                'membership_type' => $oldType,
                'membership_id' => $oldMembershipId,
            ],
            [
                'membership_type' => $newType,
                'membership_id' => $idData['membership_id'],
            ],
            targetUserId: $record->id,
        );
    }

    public static function badgeOptions(): array
    {
        return Badge::query()->where('is_active', true)->pluck('name', 'id')->all();
    }

    public static function roleOptions(): array
    {
        return [
            'member' => 'Member',
            'volunteer' => 'Volunteer',
            'moderator' => 'Moderator',
            'content_editor' => 'Content Editor',
            'admin' => 'Admin',
            'super_admin' => 'Super Admin',
        ];
    }
}
