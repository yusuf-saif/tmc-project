<?php

namespace App\Filament\Resources\MembershipApplicationResource\Pages;

use App\Filament\Resources\MembershipApplicationResource;
use Filament\Actions;
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
                        TextEntry::make('membership_status')
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
                        TextEntry::make('membership_id')->label('Membership ID'),
                        TextEntry::make('application_submitted_at')->label('Submitted At')->dateTime('d M Y H:i'),
                        TextEntry::make('approved_at')->label('Approved At')->dateTime('d M Y H:i'),
                        TextEntry::make('approver.name')->label('Approved By'),
                        TextEntry::make('membership_fee_paid_at')->label('Payment Confirmed At')->dateTime('d M Y H:i'),
                        TextEntry::make('payment_status')
                            ->label('Payment Status')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'paid' => 'success',
                                default => 'gray',
                            }),
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
                        TextEntry::make('country')->label('Country'),
                        TextEntry::make('state')->label('State'),
                        TextEntry::make('outside_nigeria_location')->label('Location (Outside Nigeria)'),
                    ])->columns(2),

                Section::make('Contact & Demographics')
                    ->schema([
                        TextEntry::make('phone')->label('Phone'),
                        TextEntry::make('age_group')->label('Age Group')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'under_18' => 'Under 18',
                                '18_24' => '18 – 24',
                                '25_34' => '25 – 34',
                                '35_44' => '35 – 44',
                                '45_54' => '45 – 54',
                                '55_above' => '55+',
                                default => $state ?? 'N/A',
                            }),
                        TextEntry::make('marital_status')->label('Marital Status')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
                    ])->columns(2),

                Section::make('Social Media')
                    ->schema([
                        TextEntry::make('instagram_username')->label('Instagram'),
                        TextEntry::make('facebook_username')->label('Facebook'),
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
            Actions\Action::make('markUnderReview')
                ->label('Mark Under Review')
                ->color('info')
                ->visible(fn (): bool => $this->record->membership_status === 'submitted')
                ->action(function (): void {
                    $this->record->update(['membership_status' => 'under_review']);
                    Notification::make()->title('Application marked as under review')->success()->send();
                    $this->refreshFormData(['membership_status']);
                }),

            Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->visible(fn (): bool => in_array($this->record->membership_status, ['submitted', 'under_review'], true))
                ->requiresConfirmation()
                ->action(function (): void {
                    MembershipApplicationResource::approve($this->record);
                    Notification::make()->title('Application approved. Membership ID generated.')->success()->send();
                    $this->refreshFormData(['membership_status', 'membership_id', 'approved_at']);
                }),

            Actions\Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->record->membership_status, ['submitted', 'under_review'], true))
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for rejection')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    MembershipApplicationResource::reject($this->record, $data['reason']);
                    Notification::make()->title('Application rejected')->danger()->send();
                    $this->refreshFormData(['membership_status']);
                }),

            Actions\Action::make('requestCorrection')
                ->label('Request Correction')
                ->color('warning')
                ->visible(fn (): bool => in_array($this->record->membership_status, ['submitted', 'under_review'], true))
                ->form([
                    Textarea::make('notes')
                        ->label('What needs to be corrected?')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    MembershipApplicationResource::requestCorrection($this->record, $data['notes']);
                    Notification::make()->title('Correction requested')->warning()->send();
                    $this->refreshFormData(['membership_status']);
                }),

            Actions\Action::make('confirmPayment')
                ->label('Confirm Payment')
                ->color('success')
                ->visible(fn (): bool => $this->record->membership_status === 'approved_pending_payment')
                ->requiresConfirmation()
                ->action(function (): void {
                    MembershipApplicationResource::confirmPayment($this->record);
                    Notification::make()->title('Payment confirmed. Membership activated.')->success()->send();
                    $this->refreshFormData(['membership_status', 'payment_status', 'membership_fee_paid_at']);
                }),
        ];
    }
}
