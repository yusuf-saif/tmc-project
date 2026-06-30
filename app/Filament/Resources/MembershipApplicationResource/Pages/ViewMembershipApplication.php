<?php

namespace App\Filament\Resources\MembershipApplicationResource\Pages;

use App\Filament\Resources\MembershipApplicationResource;
use Carbon\Carbon;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
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
                                'registered' => 'gray',
                                'active' => 'success',
                                'member' => 'primary',
                                'suspended' => 'danger',
                                'onboarding' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('membership_type')->label('Membership Type'),
                        TextEntry::make('membership_id')->label('Membership ID')
                            ->copyable()
                            ->copyMessage('ID copied'),
                        TextEntry::make('submitted_at')->label('Submitted At')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                        TextEntry::make('reviewed_at')->label('Reviewed At')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                        TextEntry::make('approver.name')->label('Approved By'),
                        TextEntry::make('approved_at')->label('Approved At')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
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
                        TextEntry::make('payment_verified_at')->label('Payment Verified At')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                        TextEntry::make('payment_source')->label('Payment Source')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'paystack' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'paystack' => 'Paystack',
                                default => '—',
                            }),
                        TextEntry::make('paystack_reference')->label('Paystack Reference')
                            ->copyable()
                            ->copyMessage('Reference copied'),
                        TextEntry::make('paystack_customer_code')->label('Paystack Customer Code'),
                        TextEntry::make('payment_failed_reason')->label('Failure Reason')
                            ->visible(fn ($record): bool => $record->onboarding_status === 'payment_failed' && $record->payment_failed_reason !== null),
                        TextEntry::make('preferred_billing_cycle')->label('Billing Preference')
                            ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'monthly')),
                        TextEntry::make('next_due_at')->label('Next Due')->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->hijri('d M Y H:i') : '—'),
                    ])->columns(3)
                    ->visible(fn ($record): bool => in_array($record->onboarding_status, ['active', 'member', 'onboarding'], true)),


            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
