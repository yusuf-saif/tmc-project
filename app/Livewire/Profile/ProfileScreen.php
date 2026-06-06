<?php

namespace App\Livewire\Profile;

use App\Services\CoinsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProfileScreen extends Component
{
    public function getProfileProperty()
    {
        return auth()->user()->profile;
    }

    public function getCoinsBalanceProperty(): int
    {
        return CoinsService::getBalance(auth()->user());
    }

    public function getBadgesProperty()
    {
        return auth()->user()->userBadges()->with('badge')->get();
    }

    public function getInterestsProperty()
    {
        return auth()->user()->interests;
    }

    public function getMemberSinceProperty(): string
    {
        return auth()->user()->created_at->format('M Y');
    }

    public function roleBadge(): array
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return ['label' => 'Admin', 'style' => 'background: var(--gold); color: var(--teal-dk);'];
        }

        if ($user->hasAnyRole(['volunteer', 'moderator'])) {
            return ['label' => ucfirst($user->getRoleNames()->first() ?? 'Member'), 'style' => 'background: #3D1A47; color: white;'];
        }

        return ['label' => 'Member', 'style' => 'background: var(--teal); color: white;'];
    }

    public function render(): View
    {
        return view('livewire.profile.profile-screen')
            ->layout('layouts.app', ['title' => 'Profile']);
    }
}
