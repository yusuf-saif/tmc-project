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
use Illuminate\Support\Facades\Storage;

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
                                'approved_pending_payment' => 'info',
                                'payment_submitted' => 'info',
                                'active' => 'success',
                                'rejected' => 'danger',
                                'needs_correction' => 'danger',
                                'in_progress' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('membership_type')->label('Membership Type'),
                        TextEntry::make('membership_id')->label('Membership ID')
                            ->copyable()
                            ->copyMessage('ID copied'),
                        TextEntry::make('submitted_at')->label('Submitted At')->dateTime('d M Y H:i'),
                        TextEntry::make('reviewed_at')->label('Reviewed At')->dateTime('d M Y H:i'),
                        TextEntry::make('reviewer.name')->label('Reviewed By'),
                        TextEntry::make('approved_at')->label('Approved At')->dateTime('d M Y H:i'),
                        TextEntry::make('approver.name')->label('Approved By'),
                        TextEntry::make('rejection_reason')->label('Rejection Reason')
                            ->visible(fn ($record): bool => $record->onboarding_status === 'rejected' && $record->rejection_reason !== null),
                        TextEntry::make('needs_correction_notes')->label('Correction Notes')
                            ->visible(fn ($record): bool => $record->onboarding_status === 'needs_correction' && $record->needs_correction_notes !== null),
                    ])->columns(3),

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
                        TextEntry::make('preferred_billing_cycle')->label('Billing Preference')
                            ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'monthly')),
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

                Section::make('Payment Details')
                    ->schema([
                        TextEntry::make('payment_submitted_at')->label('Payment Submitted At')->dateTime('d M Y H:i'),
                        TextEntry::make('payment_proof_path')->label('Payment Proof')->url(fn ($state) => $state ? Storage::url($state) : null)->visible(fn ($state) => $state !== null),
                        TextEntry::make('payment_verified_at')->label('Payment Verified At')->dateTime('d M Y H:i'),
                        TextEntry::make('payment_verified_by')->label('Verified By'),
                    ])->columns(2)
                    ->visible(fn ($record): bool => in_array($record->onboarding_status, ['payment_submitted', 'active'], true)),

                Section::make('Approval Preview')
                    ->description('What happens when you approve this application')
                    ->schema([
                        TextEntry::make('coin_preview')
                            ->label('Coin Reward')
                            ->state(fn () => "{$coinReward} Jannah Coins will be credited to user wallet"),
                        TextEntry::make('membership_preview')
                            ->label('Membership ID')
                            ->state(fn () => 'A unique membership ID will be generated'),
                        TextEntry::make('payment_preview')
                            ->label('Payment Required')
                            ->state(fn () => 'User will be prompted to complete payment before activation'),
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
                ->modalDescription("This will generate a membership ID and transition the user to \"approved — pending payment\". {$coinReward} Jannah Coins will be credited.")
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
                ->action(function (array $data) use ($coinReward): void {
                    if (! in_array($this->record->fresh()->onboarding_status, ['pending_review'], true)) {
                        Notification::make()
                            ->title('Already approved')
                            ->body('This application has already been processed.')
                            ->warning()
                            ->send();

                        return;
                    }

                    MembershipApplicationResource::approve($this->record, $data['membership_type']);
                    Notification::make()
                        ->title('Application approved')
                        ->body("Membership ID generated. {$coinReward} coins credited. User must pay before activation.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['onboarding_status', 'membership_id', 'reviewed_at', 'approved_at']);
                }),

            Actions\Action::make('needs_correction')
                ->label('Request Correction')
                ->color('warning')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->record->onboarding_status === 'pending_review')
                ->requiresConfirmation()
                ->modalHeading('Request Application Correction')
                ->modalDescription('The applicant will be notified and asked to update their application.')
                ->modalSubmitActionLabel('Send Request')
                ->form([
                    Textarea::make('notes')
                        ->label('What needs to be corrected?')
                        ->required()
                        ->placeholder('Please specify what the applicant needs to update...'),
                ])
                ->action(function (array $data): void {
                    MembershipApplicationResource::needsCorrection($this->record, $data['notes']);
                    Notification::make()->title('Correction requested')->warning()->send();
                    $this->refreshFormData(['onboarding_status', 'needs_correction_notes']);
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
                    $this->refreshFormData(['onboarding_status', 'rejection_reason']);
                }),

            Actions\Action::make('confirm_payment')
                ->label('Confirm Payment')
                ->color('success')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => $this->record->onboarding_status === 'payment_submitted')
                ->requiresConfirmation()
                ->modalHeading('Confirm Membership Payment')
                ->modalDescription('This will activate the user\'s account and give them full access.')
                ->modalSubmitActionLabel('Yes, Confirm Payment')
                ->action(function (): void {
                    MembershipApplicationResource::confirmPayment($this->record);
                    Notification::make()
                        ->title('Payment confirmed')
                        ->body('User account activated. Full access granted.')
                        ->success()
                        ->send();
                    $this->refreshFormData(['onboarding_status', 'payment_verified_at', 'activated_at']);
                }),
        ];
    }
}
