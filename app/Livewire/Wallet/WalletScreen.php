<?php

namespace App\Livewire\Wallet;

use App\Models\UserReferral;
use App\Services\CoinsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class WalletScreen extends Component
{
    use WithPagination;

    public bool $copied = false;

    public bool $showHistory = false;

    public function getBalanceProperty(): int
    {
        return CoinsService::getBalance(auth()->user());
    }

    public function getReferralLinkProperty(): string
    {
        return url('/membership/signup?ref='.auth()->user()->referral_code);
    }

    public function getReferralCountProperty(): int
    {
        return UserReferral::query()
            ->where('referrer_id', auth()->id())
            ->count();
    }

    public function getHistoryProperty()
    {
        return CoinsService::getHistory(auth()->user());
    }

    public function copyLink(): void
    {
        $this->copied = true;
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function render(): View
    {
        return view('livewire.wallet.wallet-screen')
            ->layout('layouts.app', ['title' => 'My Wallet']);
    }
}
