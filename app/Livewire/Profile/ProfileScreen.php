<?php

namespace App\Livewire\Profile;

use App\Models\UserReferral;
use App\Services\CoinsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ProfileScreen extends Component
{
    use WithPagination;

    public string $tab = 'overview';

    public bool $showHistory = false;

    public bool $copied = false;

    protected $listeners = ['switchTab' => 'switchTab'];

    public function mount(): void
    {
        $this->tab = request()->query('tab', 'overview');
    }

    public function switchTab(string $tab): void
    {
        $validTabs = ['overview', 'wallet', 'membership', 'notifications', 'referrals', 'settings'];
        $this->tab = in_array($tab, $validTabs) ? $tab : 'overview';
    }

    // ─── Overview properties ───────────────────────────────────────

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
        return auth()->user()->loadMissing('interests')->interests ?? collect();
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

    // ─── Wallet properties ─────────────────────────────────────────

    public function getReferralLinkProperty(): string
    {
        return url('/register').'?ref='.auth()->user()->referral_code;
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

    // ─── Notifications properties ──────────────────────────────────

    public function getNotificationsProperty()
    {
        return auth()->user()->notifications()->latest()->paginate(15);
    }

    public function getUnreadCountProperty(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    // ─── Referrals properties ──────────────────────────────────────

    public function getReferralsProperty()
    {
        return UserReferral::query()
            ->where('referrer_id', auth()->id())
            ->with('referred')
            ->latest()
            ->get();
    }

    // ─── Tab navigation ────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.profile.profile-screen')
            ->layout('layouts.app', ['title' => $this->tabTitle()]);
    }

    protected function tabTitle(): string
    {
        return match ($this->tab) {
            'wallet' => 'My Wallet',
            'membership' => 'Membership Card',
            'notifications' => 'Notifications',
            'referrals' => 'Referrals',
            'settings' => 'Settings',
            default => 'Profile',
        };
    }
}
