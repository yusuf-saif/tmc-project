<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Notifications\SetPasswordNotification;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('suspend')
                ->label('Suspend')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status === 'active')
                ->form([
                    Textarea::make('reason')
                        ->label('Reason for suspension')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    UserResource::suspend($this->record, $data['reason']);
                    Notification::make()->title('Member suspended')->success()->send();
                }),
            Actions\Action::make('reactivate')
                ->label('Reactivate')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'suspended')
                ->action(function (): void {
                    UserResource::reactivate($this->record);
                    Notification::make()->title('Member reactivated')->success()->send();
                }),
            Actions\Action::make('awardBadge')
                ->label('Award Badge')
                ->form([
                    Select::make('badge_id')
                        ->options(UserResource::badgeOptions())
                        ->required(),
                ])
                ->action(function (array $data): void {
                    UserResource::awardBadge($this->record, (int) $data['badge_id']);
                    Notification::make()->title('Badge awarded')->success()->send();
                }),
            Actions\Action::make('changeMembershipType')
                ->label('Change Member Type')
                ->icon('heroicon-o-identification')
                ->requiresConfirmation()
                ->form([
                    Select::make('new_type')
                        ->label('Membership Type')
                        ->options([
                            'M' => 'Member (M)',
                            'SM' => 'SixtenMember (SM)',
                            'E' => 'Executive (E)',
                        ])
                        ->default(fn () => $this->record->memberProfile?->membership_type ?? 'M')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    UserResource::changeMembershipType($this->record, $data['new_type']);
                    Notification::make()
                        ->title('Membership type updated')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('changeRole')
                ->label('Change Role')
                ->visible(fn (): bool => UserResource::canChangeRole(auth()->user()))
                ->form([
                    Select::make('new_role')
                        ->options(UserResource::roleOptions())
                        ->required(),
                    Textarea::make('reason'),
                ])
                ->action(function (array $data): void {
                    UserResource::changeRole($this->record, $data['new_role'], $data['reason'] ?? null);
                    Notification::make()->title('Role updated')->success()->send();
                }),
            Actions\Action::make('awardCoins')
                ->label('Award Coins')
                ->form([
                    TextInput::make('amount')->numeric()->minValue(1)->required(),
                    Textarea::make('reason')->required(),
                ])
                ->action(function (array $data): void {
                    UserResource::awardCoins($this->record, (int) $data['amount'], $data['reason']);
                    Notification::make()->title('Coins awarded')->success()->send();
                }),
            Actions\Action::make('deductCoins')
                ->label('Deduct Coins')
                ->color('danger')
                ->form([
                    TextInput::make('amount')->numeric()->minValue(1)->required(),
                    Textarea::make('reason')->required(),
                ])
                ->action(function (array $data): void {
                    UserResource::deductCoins($this->record, (int) $data['amount'], $data['reason']);
                    Notification::make()->title('Coins deducted')->success()->send();
                }),
            Actions\Action::make('resendPasswordSetup')
                ->label('Resend Password Setup Link')
                ->icon('heroicon-o-envelope')
                ->requiresConfirmation()
                ->modalHeading('Resend Password Setup Link')
                ->modalDescription('This will send a new password setup email to '.($this->record->email ?? 'this user').'. The link expires in 60 minutes.')
                ->modalSubmitActionLabel('Send')
                ->action(function (): void {
                    $token = Password::broker()->createToken($this->record);
                    Notification::sendNow($this->record, new SetPasswordNotification($token));
                    Filament\Notifications\Notification::make()
                        ->title('Password setup link sent')
                        ->success()
                        ->send();
                }),
        ];
    }
}
