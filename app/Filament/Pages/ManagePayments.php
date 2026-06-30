<?php

namespace App\Filament\Pages;

use App\Filament\Resources\MembershipApplicationResource;
use App\Models\MemberProfile;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

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
                    ->where('onboarding_status', 'onboarding')
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
                Tables\Columns\TextColumn::make('onboarding_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'onboarding' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'onboarding' => 'Awaiting Payment',
                        default => str($state)->replace('_', ' ')->title(),
                    }),
                Tables\Columns\TextColumn::make('paystack_reference')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_failed_reason')
                    ->label('Failure Reason')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('payment_verified_at')
                    ->label('Verified')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('onboarding_status')
                    ->label('Status')
                    ->options([
                        'onboarding' => 'Awaiting Payment',
                    ]),
            ])
            ->actions([
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

        return $user?->hasAnyRole(['super_admin', 'admin', 'moderator']) ?? false;
    }
}
