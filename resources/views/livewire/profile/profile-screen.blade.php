@php($roleBadge = $this->roleBadge())
@php($displayName = $this->profile?->display_name ?: auth()->user()->name)

<div class="anim-fade-in" x-data="{ activeTab: @js($tab) }" x-effect="$wire.tab = activeTab">

  {{-- Profile Header --}}
  <div class="profile-banner"></div>
  <div class="profile-avatar-wrap anim-scale-in">
    @if ($this->profile?->avatar_path && Storage::disk('public')->exists($this->profile->avatar_path))
      <img src="{{ Storage::url($this->profile->avatar_path) }}"
           alt="{{ $displayName }}"
           class="profile-avatar">
    @else
      <div class="profile-avatar-initials">
        {{ strtoupper(mb_substr($displayName, 0, 1)) }}
      </div>
    @endif
    <h1 class="profile-name">{{ $displayName }}</h1>
    <span class="profile-badge {{ $roleBadge['class'] ?? '' }}"
          style="{{ $roleBadge['style'] }}">
      {{ $roleBadge['label'] }}
    </span>
  </div>

  {{-- Tab Navigation --}}
  <div class="tab-scroll">
    <div class="tab-scroll-inner">
      @foreach([
        'overview' => 'Overview',
        'wallet' => 'Wallet',
        'membership' => 'Membership',
        'notifications' => 'Notifications',
        'referrals' => 'Referrals',
        'settings' => 'Settings',
      ] as $key => $label)
      <button
        wire:click="switchTab('{{ $key }}')"
        x-on:click="activeTab = '{{ $key }}'"
        class="tab-pill {{ $tab === $key ? 'tab-pill-active' : 'tab-pill-inactive' }}">
        {{ $label }}
      </button>
      @endforeach
    </div>
  </div>

  {{-- ═══════════ TAB: OVERVIEW ═══════════ --}}
  @if($tab === 'overview')
  <div class="anim-fade-up">

    {{-- Stats --}}
    <div class="profile-stats">
      <div class="profile-stat-card">
        <p class="profile-stat-val">{{ $this->memberSince }}</p>
        <p class="profile-stat-lbl">Since</p>
      </div>
      <button wire:click="switchTab('wallet')" x-on:click="activeTab = 'wallet'"
              class="profile-stat-card"
              style="text-decoration:none;cursor:pointer;background:none;border:none;text-align:center;position:relative;">
        <p class="profile-stat-val">{{ number_format($this->coinsBalance) }}</p>
        <p class="profile-stat-lbl">Coins <span style="font-size:8px;color:var(--teal);">›</span></p>
      </button>
      <button wire:click="switchTab('membership')" x-on:click="activeTab = 'membership'"
              class="profile-stat-card"
              style="text-decoration:none;cursor:pointer;background:none;border:none;text-align:center;position:relative;">
        <p class="profile-stat-val">{{ $this->badges->count() }}</p>
        <p class="profile-stat-lbl">Badges <span style="font-size:8px;color:var(--teal);">›</span></p>
      </button>
    </div>

    {{-- Interests --}}
    @if($this->interests->isNotEmpty())
    <div class="anim-fade-up delay-1 page-pad" style="margin-bottom:16px;">
      <p class="section-label profile-section-label">Interests</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach ($this->interests as $interest)
          <span class="pill">{{ $interest->name }}</span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="anim-fade-up delay-2 page-pad" style="margin-bottom:16px;">
      <p class="section-label profile-section-label">Quick Access</p>
      <div class="quick-access-grid">
        <button wire:click="switchTab('wallet')" x-on:click="activeTab = 'wallet'"
                class="quick-access-tile">
          <span class="quick-access-tile-emoji">💰</span>
          <div>
            <p class="quick-access-tile-title">Wallet</p>
            <p class="quick-access-tile-sub">{{ number_format($this->coinsBalance) }} coins</p>
          </div>
        </button>
        <button wire:click="switchTab('notifications')" x-on:click="activeTab = 'notifications'"
                class="quick-access-tile">
          <span class="quick-access-tile-emoji">🔔</span>
          <div>
            <p class="quick-access-tile-title">Notifications</p>
            <p class="quick-access-tile-sub">{{ $this->unreadCount }} unread</p>
          </div>
        </button>
        <button wire:click="switchTab('referrals')" x-on:click="activeTab = 'referrals'"
                class="quick-access-tile">
          <span class="quick-access-tile-emoji">🤝</span>
          <div>
            <p class="quick-access-tile-title">Referrals</p>
            <p class="quick-access-tile-sub">{{ $this->referralCount }} joined</p>
          </div>
        </button>
        <button wire:click="switchTab('membership')" x-on:click="activeTab = 'membership'"
                class="quick-access-tile">
          <span class="quick-access-tile-emoji">🪪</span>
          <div>
            <p class="quick-access-tile-title">Membership</p>
            <p class="quick-access-tile-sub">Legacy card</p>
          </div>
        </button>
      </div>
    </div>
  </div>

  {{-- ═══════════ TAB: WALLET ═══════════ --}}
  @elseif($tab === 'wallet')
  <div class="anim-fade-up page-pad" style="padding-top:16px;padding-bottom:16px;"
       x-data="{ open: $wire.entangle('showHistory') }">

    {{-- Balance Card --}}
    <section class="wallet-balance-card">
      <div class="wallet-balance-icon">✦</div>
      <p class="wallet-balance-amount">{{ number_format($this->coinsBalance) }}</p>
      <p class="wallet-balance-label">Jannah Coins</p>
    </section>

    {{-- Referral Section --}}
    <section class="referral-section" style="margin-top:16px;">
      <h2>Invite a Sister, Earn 25 Coins</h2>
      <p>When a sister joins using your link, you both benefit</p>
      <div class="referral-code-box" x-data="{ copied: false, fallback: '' }">
        <p class="referral-code-text">{{ auth()->user()->referral_code }}</p>
        <button type="button" class="referral-code-copy-btn"
          x-on:click="
            try {
              await navigator.clipboard.writeText('{{ $this->referralLink }}');
              copied = true;
            } catch(e) {
              fallback = '{{ $this->referralLink }}';
              $refs.fallbackInput.select();
              document.execCommand('copy');
              copied = true;
            }
            setTimeout(() => copied = false, 2000)"
          x-text="copied ? 'Copied!' : 'Copy'">
        </button>
        <input type="text" x-ref="fallbackInput" x-model="fallback" class="sr-only" aria-hidden="true">
      </div>
      <p class="referral-stat" style="margin-top:8px;">{{ $this->referralCount }} sister(s) joined with your link</p>
    </section>

    {{-- History --}}
    <section class="wallet-history">
      <button type="button" wire:click="toggleHistory" class="wallet-history-toggle"
              aria-expanded="{{ $showHistory ? 'true' : 'false' }}"
              aria-controls="wallet-history-panel">
        <span class="wallet-history-toggle-text">View History</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" class="wallet-history-toggle-icon"
             x-bind:style="open ? 'transform:rotate(180deg)' : ''">
          <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
      </button>

      <div x-show="open" x-transition x-cloak id="wallet-history-panel" style="margin-top:16px;">
        <div class="wallet-history-header">
          <span>Date</span>
          <span>Reason</span>
          <span style="text-align:right;">Amount</span>
        </div>
        @foreach ($this->history as $row)
          <div class="wallet-history-row">
            <span>{{ $row->created_at->hijri('d M Y') }}</span>
            <span>{{ match ($row->reason) {
                'onboarding' => 'Welcome gift',
                'referral' => 'Referral bonus',
                'manual' => 'Admin award',
                'admin_adjustment' => 'Adjustment',
                default => ucfirst(str_replace('_', ' ', $row->reason)),
            } }}</span>
            <span class="wallet-history-amount {{ $row->amount >= 0 ? 'wallet-history-positive' : 'wallet-history-negative' }}">
              {{ $row->amount >= 0 ? '+' : '' }}{{ $row->amount }}
            </span>
          </div>
        @endforeach
        <div class="wallet-history-links">{{ $this->history->links() }}</div>
      </div>
    </section>
  </div>

  {{-- ═══════════ TAB: MEMBERSHIP ═══════════ --}}
  @elseif($tab === 'membership')
  <div id="membership" class="anim-fade-up page-pad" style="padding-top:16px;padding-bottom:16px;">

    {{-- Membership Status --}}
    <div style="margin-bottom:20px;text-align:center;">
      <p class="section-label profile-section-label">Membership Status</p>
      @php($ms = $this->profile?->onboarding_status)
      @if($ms === 'active')
        <div style="background:var(--teal);color:white;border-radius:8px;padding:16px;margin-top:8px;">
          <p style="font-size:14px;font-weight:600;">Free Access</p>
          <p style="font-size:12px;opacity:0.85;margin-top:4px;">
            You're currently on the free plan
          </p>
          <a href="{{ route('membership.payment') }}" wire:navigate
             style="display:inline-block;margin-top:10px;background:white;color:var(--teal);padding:6px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">
            Choose Your Plan →
          </a>
        </div>
      @elseif($ms === 'member')
        <div style="background:var(--gold);color:var(--teal-dk);border-radius:8px;padding:16px;margin-top:8px;">
          <p style="font-size:14px;font-weight:600;">Active Member</p>
          @if($this->profile?->current_period_ends_at)
          <p style="font-size:12px;opacity:0.85;margin-top:4px;">
            Valid until {{ $this->profile->current_period_ends_at->hijri('d M Y') }}
          </p>
          @endif
        </div>
      @elseif($ms === 'suspended')
        <div style="background:#3D1A47;color:white;border-radius:8px;padding:16px;margin-top:8px;">
          <p style="font-size:14px;font-weight:600;">Membership Lapsed</p>
          <p style="font-size:12px;opacity:0.85;margin-top:4px;">
            Your membership has ended — renew to regain access
          </p>
          <a href="{{ route('membership.payment') }}"
             style="display:inline-block;margin-top:10px;background:white;color:#3D1A47;padding:6px 16px;border-radius:6px;font-size:13px;font-weight:600;text-decoration:none;">
            Renew Now →
          </a>
        </div>
      @else
        <p class="membership-empty" style="margin-top:8px;">No membership data available</p>
      @endif
    </div>

    {{-- Badges --}}
    <div style="margin-bottom:20px;">
      <p class="section-label profile-section-label">Badges</p>
      @if ($this->badges->isNotEmpty())
        <div class="badge-scroll">
          @foreach ($this->badges as $userBadge)
            <div class="badge-item">
              @if ($userBadge->badge?->icon_path)
                <img src="{{ Storage::url($userBadge->badge->icon_path) }}"
                     alt="{{ $userBadge->badge->name }}"
                     class="badge-icon">
              @else
                <div class="badge-icon-placeholder">✦</div>
              @endif
              <p class="badge-name">{{ $userBadge->badge?->name }}</p>
            </div>
          @endforeach
        </div>
      @else
        <p class="membership-empty">Badges will appear here as you earn them</p>
      @endif
    </div>

    {{-- Legacy Card --}}
    <div style="text-align:center;">
      <p class="section-label profile-section-label">Legacy Card</p>
      <div class="membership-legacy-preview">
        <div class="membership-legacy-header">
          <img src="{{ asset('images/img1.png') }}" alt="TMC" class="membership-legacy-logo">
          <p class="membership-legacy-name">{{ $displayName }}</p>
          <p class="membership-legacy-coins">{{ number_format($this->coinsBalance) }} coins</p>
        </div>
      </div>
      <a href="{{ route('profile.legacy-card') }}" class="membership-legacy-link">
        View Full Card →
      </a>
    </div>
  </div>

  {{-- ═══════════ TAB: NOTIFICATIONS ═══════════ --}}
  @elseif($tab === 'notifications')
  <div class="anim-fade-up page-pad" style="padding-top:16px;padding-bottom:16px;">

    {{-- Notification Preferences Link --}}
    <a href="{{ route('profile.notifications') }}" class="notif-pref-link">
      <span class="notif-pref-text">⚙️ Notification Preferences</span>
      <span class="notif-pref-arrow">›</span>
    </a>

    {{-- Notifications List --}}
    @php($notifications = $this->notifications)
    @php($unread = $this->unreadCount)
    @if($notifications->count() > 0)
      @if($unread > 0)
        <button type="button" wire:click="markAllAsRead" wire:confirm="Mark all notifications as read?"
                class="notif-mark-all">
          <span class="notif-mark-all-text">Mark all as read</span>
          <span class="notif-mark-all-icon">✓</span>
        </button>
      @endif
      <div class="notif-list">
        @foreach($notifications as $notification)
          <div class="notif-card {{ $notification->read_at ? '' : 'notif-card-unread' }}"
               wire:click="markAsRead('{{ $notification->id }}')"
               wire:key="notif-{{ $notification->id }}">
            <div class="notif-item-inner">
              @if(!$notification->read_at)
                <span class="notif-dot"></span>
              @endif
              <div class="notif-item-body">
                <p class="notif-title">{{ $notification->data['title'] ?? $notification->type }}</p>
                @if(isset($notification->data['body']))
                  <p class="notif-body">{{ $notification->data['body'] }}</p>
                @endif
                <p class="notif-time">{{ $notification->created_at->diffForHumans() }}</p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="notif-list-links">{{ $notifications->links() }}</div>
    @else
      <div class="notif-list-empty">
        <p class="notif-list-empty-icon">🔔</p>
        <p class="notif-list-empty-title">No notifications yet</p>
        <p class="notif-list-empty-sub">Updates and announcements will appear here</p>
      </div>
    @endif
  </div>

  {{-- ═══════════ TAB: REFERRALS ═══════════ --}}
  @elseif($tab === 'referrals')
  <div class="anim-fade-up page-pad" style="padding-top:16px;padding-bottom:16px;">

    {{-- Referral Stats --}}
    <section class="referral-hero">
      <p class="referral-hero-count">{{ $this->referralCount }}</p>
      <p class="referral-hero-label">Sisters Joined</p>
    </section>

    {{-- Share Link --}}
    <section class="referral-card">
      <h3>Share Your Link</h3>
      <p class="referral-card-sub">Earn 25 coins for each sister who joins</p>
      <div class="referral-code-box" style="background:var(--ivory);" x-data="{ copied: false, fallback: '' }">
        <p class="referral-code-text">{{ auth()->user()->referral_code }}</p>
        <button type="button" class="referral-code-copy-btn"
          x-on:click="
            try {
              await navigator.clipboard.writeText('{{ $this->referralLink }}');
              copied = true;
            } catch(e) {
              fallback = '{{ $this->referralLink }}';
              $refs.fallbackInput2.select();
              document.execCommand('copy');
              copied = true;
            }
            setTimeout(() => copied = false, 2000)"
          x-text="copied ? 'Copied!' : 'Copy Link'">
        </button>
        <input type="text" x-ref="fallbackInput2" x-model="fallback" class="sr-only" aria-hidden="true">
      </div>
    </section>

    {{-- Referral List --}}
    @php($referrals = $this->referrals)
    @if($referrals->count() > 0)
      <div>
        <p class="section-label profile-section-label">Your Referrals</p>
        <div class="referral-list">
          @foreach($referrals as $referral)
            <div class="referral-item">
              <div class="referral-avatar">
                {{ strtoupper(mb_substr($referral->referred?->name ?? '?', 0, 1)) }}
              </div>
              <div style="flex:1;min-width:0;">
                <p class="referral-name">{{ $referral->referred?->name ?? 'Unknown' }}</p>
                <p class="referral-date">Joined {{ $referral->created_at->diffForHumans() }}</p>
              </div>
              <span class="referral-amount" title="Coins earned from this referral">+25</span>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div class="referral-list-empty">
        <p class="referral-list-empty-emoji">🤝</p>
        <p class="referral-list-empty-title">No referrals yet</p>
        <p class="referral-list-empty-sub">Share your link to invite sisters</p>
      </div>
    @endif
  </div>

  {{-- ═══════════ TAB: SETTINGS ═══════════ --}}
  @elseif($tab === 'settings')
  <div class="anim-fade-up page-pad" style="padding-top:16px;padding-bottom:16px;">
    <div class="settings-list">
      <a href="{{ route('profile.edit') }}" class="settings-item">
        <span>Edit Profile</span>
        <span class="settings-arrow">›</span>
      </a>
      <a href="{{ route('profile.notifications') }}" class="settings-item">
        <span>Notification Preferences</span>
        <span class="settings-arrow">›</span>
      </a>
      <form method="POST" action="{{ route('password.send-reset') }}" class="settings-item">
        @csrf
        <button type="submit" class="settings-reset-btn">Reset Password</button>
        <span class="settings-arrow">›</span>
      </form>
      <a href="{{ route('profile.legacy-card') }}" class="settings-item">
        <span>Legacy Card</span>
        <span class="settings-arrow">›</span>
      </a>
      <form method="POST" action="{{ route('logout') }}" class="settings-logout-form">
        @csrf
        <button type="submit" class="settings-logout-btn">Logout</button>
      </form>
    </div>
  </div>
  @endif

</div>
