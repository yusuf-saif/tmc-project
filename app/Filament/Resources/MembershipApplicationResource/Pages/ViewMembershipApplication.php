<?php

namespace App\Filament\Resources\MembershipApplicationResource\Pages;

use App\Filament\Resources\MembershipApplicationResource;
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
                        TextEntry::make('user.name')->label('Account Name'),
                        TextEntry::make('user.email')->label('Email'),
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
                            ->state(fn ($record): string => $record->user->interests->pluck('name')->join(', ') ?: 'None'),
                        TextEntry::make('user.goals')
                            ->label('Goals')
                            ->state(fn ($record): string => $record->user->goals->pluck('name')->join(', ') ?: 'None'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review')
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
                    Notification::make()->title('Application approved. Membership ID generated.')->success()->send();
                    $this->refreshFormData(['onboarding_status', 'membership_id', 'reviewed_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review')
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
