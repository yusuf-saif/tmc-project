<?php

namespace App\Livewire\Profile;

use App\Services\CoinsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LegacyCard extends Component
{
    public function getMemberSinceProperty(): string
    {
        return auth()->user()->created_at->format('M Y');
    }

    public function getCoinsBalanceProperty(): int
    {
        return CoinsService::getBalance(auth()->user());
    }

    public function render(): View
    {
        return view('livewire.profile.legacy-card')
            ->layout('layouts.card', ['title' => 'Legacy Card']);
    }
}
