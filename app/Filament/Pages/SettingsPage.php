<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\AuditLogService;
use Filament\Pages\Page;

class SettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $navigationGroup = 'Configuration';

    protected static string $view = 'filament.pages.settings-page';

    protected static ?string $slug = 'settings';

    public string $bankDetails = '';

    public string $donateMessage = '';

    public int $starterCoinsAmount = 50;

    public int $referralCoinsAmount = 25;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public function mount(): void
    {
        $this->bankDetails = Setting::getValue('bank_details');
        $this->donateMessage = Setting::getValue('donate_message');
        $this->starterCoinsAmount = (int) Setting::getValue('starter_coins_amount', '50');
        $this->referralCoinsAmount = (int) Setting::getValue('referral_coins_amount', '25');
    }

    public function save(): void
    {
        Setting::query()->updateOrCreate(['key' => 'bank_details'], ['value' => $this->bankDetails]);
        Setting::query()->updateOrCreate(['key' => 'donate_message'], ['value' => $this->donateMessage]);
        Setting::query()->updateOrCreate(['key' => 'starter_coins_amount'], ['value' => (string) $this->starterCoinsAmount]);
        Setting::query()->updateOrCreate(['key' => 'referral_coins_amount'], ['value' => (string) $this->referralCoinsAmount]);

        AuditLogService::log('settings_updated', null, [], ['keys' => ['bank_details', 'donate_message', 'starter_coins_amount', 'referral_coins_amount']]);
        session()->flash('success', 'Settings saved');
    }
}
