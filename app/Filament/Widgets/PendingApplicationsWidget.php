<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MembershipApplicationResource;
use App\Models\MemberProfile;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Schema;

class PendingApplicationsWidget extends TableWidget
{
    protected static ?string $heading = 'Pending Membership Applications';

    protected static ?int $sort = 1;

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    public static function canView(): bool
    {
        return Schema::hasTable('member_profiles');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MemberProfile::query()
                    ->where('onboarding_status', 'pending_review')
                    ->with('user')
                    ->latest('submitted_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Applicant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email'),
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
            ->paginated(false)
            ->emptyStateHeading('No pending applications')
            ->emptyStateDescription('All membership applications have been reviewed.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('View all')
                    ->url(MembershipApplicationResource::getUrl('index')),
            ]);
    }
}
