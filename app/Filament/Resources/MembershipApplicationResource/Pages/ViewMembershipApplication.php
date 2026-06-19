<?php

namespace App\Filament\Resources\MembershipApplicationResource\Pages;

use App\Filament\Resources\MembershipApplicationResource;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewMembershipApplication extends ViewRecord
{
    protected static string $resource = MembershipApplicationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $coinReward = (int) Setting::getValue('membership_approval_coins', '100');

        return $infolist
            ->schema([
                Section::make('Application Status')
                    ->schema([
                        TextEntry::make('onboarding_status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending_review' => 'warning',
                                'approved' => 'success',
                                'active' => 'success',
                                'rejected' => 'danger',
                                'in_progress' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('membership_type')->label('Membership Type'),
                        TextEntry::make('membership_id')->label('Membership ID'),
                        TextEntry::make('submitted_at')->label('Submitted At')->dateTime('d M Y H:i'),
                        TextEntry::make('reviewed_at')->label('Reviewed At')->dateTime('d M Y H:i'),
                        TextEntry::make('reviewer.name')->label('Reviewed By'),
                    ])->columns(2),

                Section::make('Personal Details')
                    ->schema([
                        TextEntry::make('first_name')->label('First Name'),
                        TextEntry::make('last_name')->label('Last Name'),
                        TextEntry::make('nickname')->label('Nickname'),
                        TextEntry::make('user.name')->label('Account Name')->placeholder('N/A'),
                        TextEntry::make('user.email')->label('Email')->placeholder('N/A'),
                    ])->columns(2),

                Section::make('Location')
                    ->schema([
                        TextEntry::make('location_country')->label('Country'),
                        TextEntry::make('location_state')->label('State'),
                        TextEntry::make('location_international')->label('Location (Outside Nigeria)'),
                    ])->columns(2),

                Section::make('Contact & Demographics')
                    ->schema([
                        TextEntry::make('phone')->label('Phone'),
                        TextEntry::make('age_group')->label('Age Group')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'under_18' => 'Under 18',
                                '18_24' => '18 - 24',
                                '25_34' => '25 - 34',
                                '35_44' => '35 - 44',
                                '45_54' => '45 - 54',
                                '55_above' => '55+',
                                default => $state ?? 'N/A',
                            }),
                        TextEntry::make('marital_status')->label('Marital Status')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
                    ])->columns(2),

                Section::make('Social Media')
                    ->schema([
                        TextEntry::make('ig_username')->label('Instagram'),
                        TextEntry::make('fb_username')->label('Facebook'),
                        TextEntry::make('x_username')->label('X (Twitter)'),
                        TextEntry::make('tiktok_username')->label('TikTok'),
                    ])->columns(2),

                Section::make('Interests & Goals')
                    ->schema([
                        TextEntry::make('user.interests')
                            ->label('Interests')
                            ->state(fn ($record): string => $record->user?->interests?->pluck('name')?->join(', ') ?: 'None'),
                        TextEntry::make('user.goals')
                            ->label('Goals')
                            ->state(fn ($record): string => $record->user?->goals?->pluck('name')?->join(', ') ?: 'None'),
                    ]),

                Section::make('Approval Preview')
                    ->description('What happens when you approve this application')
                    ->schema([
                        TextEntry::make('coin_preview')
                            ->label('Coin Reward')
                            ->state(fn () => "{$coinReward} Jannah Coins will be credited to user wallet"),
                        TextEntry::make('activation_preview')
                            ->label('Account Activation')
                            ->state(fn () => 'User status will be set to active'),
                        TextEntry::make('membership_preview')
                            ->label('Membership ID')
                            ->state(fn () => 'A unique membership ID will be generated'),
                    ])->columns(3)
                    ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $coinReward = (int) Setting::getValue('membership_approval_coins', '100');

        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Approve Membership Application')
                ->modalDescription("This will activate the user's account and allocate {$coinReward} Jannah Coins.")
                ->modalSubmitActionLabel('Yes, Approve')
                ->form([
                    Select::make('membership_type')
                        ->label('Membership Type')
                        ->options([
                            'M' => 'M - Member',
                            'SM' => 'SM - Student Member',
                            'E' => 'E - Exco',
                        ])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    MembershipApplicationResource::approve($this->record, $data['membership_type']);
                    Notification::make()
                        ->title('Application approved')
                        ->body("Membership ID generated. {$coinReward} coins credited to user.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['onboarding_status', 'membership_id', 'reviewed_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Reject Membership Application')
                ->modalDescription('This action cannot be undone. The applicant will be notified.')
                ->modalSubmitActionLabel('Yes, Reject')
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for rejection')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    MembershipApplicationResource::reject($this->record, $data['reason']);
                    Notification::make()->title('Application rejected')->danger()->send();
                    $this->refreshFormData(['onboarding_status']);
                }),
        ];
    }
}
