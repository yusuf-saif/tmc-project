@php($roleBadge = $this->roleBadge())
@php($displayName = $this->profile?->display_name ?: auth()->user()->name)

<div class="anim-fade-in" x-data="{ activeTab: @js($tab) }" x-effect="$wire.tab = activeTab">

  {{-- Profile Header (always visible) --}}
  <div class="profile-banner"></div>
  <div class="profile-avatar-wrap anim-scale-in">
    @if ($this->profile?->avatar_path)
      <img src="{{ Storage::url($this->profile->avatar_path) }}"
           alt="{{ $displayName }}"
           class="profile-avatar">
    @else
      <div class="profile-avatar-initials">
        {{ strtoupper(mb_substr($displayName, 0, 1)) }}
      </div>
    @endif
    <h1 style="font-family:var(--font-display);font-size:1.6rem;
               color:var(--teal-dk);line-height:1;margin-top:10px;
               margin-bottom:6px;">
      {{ $displayName }}
    </h1>
    <span style="display:inline-flex;padding:4px 12px;border-radius:20px;
                 font-family:var(--font-body);font-size:10px;
                 font-weight:600;text-transform:uppercase;letter-spacing:1px;
                 {{ $roleBadge['style'] }}">
      {{ $roleBadge['label'] }}
    </span>
  </div>

  {{-- Tab Navigation --}}
  <div style="padding:12px 16px 0;overflow-x:auto;scrollbar-width:none;">
    <div style="display:flex;gap:4px;min-width:max-content;">
      @foreach([
        'overview' => 'Overview',
        'wallet' => 'Wallet',
        'membership' => 'Card',
        'notifications' => 'Alerts',
        'referrals' => 'Referrals',
        'settings' => 'Settings',
      ] as $key => $label)
      <button
        wire:click="switchTab('{{ $key }}')"
        x-on:click="activeTab = '{{ $key }}'"
        style="font-family:var(--font-body);font-size:12px;font-weight:600;
               padding:8px 14px;border-radius:20px;border:none;cursor:pointer;
               transition:all 0.2s;white-space:nowrap;
               background:{{ $tab === $key ? 'var(--teal)' : 'var(--teal-lt)' }};
               color:{{ $tab === $key ? 'white' : 'var(--teal)' }};">
        {{ $label }}
      </button>
      @endforeach
    </div>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: OVERVIEW                                                  --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @if($tab === 'overview')
  <div class="anim-fade-up">

    {{-- Stats --}}
    <div class="profile-stats">
      <div class="profile-stat-card">
        <p class="profile-stat-val">{{ $this->memberSince }}</p>
        <p class="profile-stat-lbl">Since</p>
      </div>
      <button wire:click="switchTab('wallet')" x-on:click="activeTab = 'wallet'"
              class="profile-stat-card" style="text-decoration:none;cursor:pointer;
                     background:none;border:none;text-align:center;">
        <p class="profile-stat-val">{{ number_format($this->coinsBalance) }}</p>
        <p class="profile-stat-lbl">Coins</p>
      </button>
      <button wire:click="switchTab('membership')" x-on:click="activeTab = 'membership'"
              class="profile-stat-card" style="text-decoration:none;cursor:pointer;
                     background:none;border:none;text-align:center;">
        <p class="profile-stat-val">{{ $this->badges->count() }}</p>
        <p class="profile-stat-lbl">Badges</p>
      </button>
    </div>

    {{-- Interests --}}
    @if($this->interests->isNotEmpty())
    <div class="anim-fade-up delay-1" style="padding:0 16px 16px;">
      <p class="section-label" style="padding:0;margin-bottom:10px;">Interests</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;">
        @foreach ($this->interests as $interest)
          <span style="display:inline-flex;padding:5px 12px;
                       border-radius:20px;font-size:12px;
                       color:var(--teal);background:var(--teal-lt);">
            {{ $interest->name }}
          </span>
        @endforeach
      </div>
    </div>
    @endif

    {{-- Quick Actions --}}
    <div class="anim-fade-up delay-2" style="padding:0 16px 16px;">
      <p class="section-label" style="padding:0;margin-bottom:10px;">Quick Access</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
        <button wire:click="switchTab('wallet')" x-on:click="activeTab = 'wallet'"
                style="display:flex;align-items:center;gap:10px;padding:14px;
                       border-radius:var(--radius-md);background:white;
                       border:1px solid var(--border);cursor:pointer;text-align:left;">
          <span style="font-size:20px;">💰</span>
          <div>
            <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;">Wallet</p>
            <p style="font-size:11px;color:var(--ink-soft);margin:0;">{{ number_format($this->coinsBalance) }} coins</p>
          </div>
        </button>
        <button wire:click="switchTab('notifications')" x-on:click="activeTab = 'notifications'"
                style="display:flex;align-items:center;gap:10px;padding:14px;
                       border-radius:var(--radius-md);background:white;
                       border:1px solid var(--border);cursor:pointer;text-align:left;">
          <span style="font-size:20px;">🔔</span>
          <div>
            <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;">Notifications</p>
            <p style="font-size:11px;color:var(--ink-soft);margin:0;">{{ $this->unreadCount }} unread</p>
          </div>
        </button>
        <button wire:click="switchTab('referrals')" x-on:click="activeTab = 'referrals'"
                style="display:flex;align-items:center;gap:10px;padding:14px;
                       border-radius:var(--radius-md);background:white;
                       border:1px solid var(--border);cursor:pointer;text-align:left;">
          <span style="font-size:20px;">🤝</span>
          <div>
            <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;">Referrals</p>
            <p style="font-size:11px;color:var(--ink-soft);margin:0;">{{ $this->referralCount }} joined</p>
          </div>
        </button>
        <button wire:click="switchTab('membership')" x-on:click="activeTab = 'membership'"
                style="display:flex;align-items:center;gap:10px;padding:14px;
                       border-radius:var(--radius-md);background:white;
                       border:1px solid var(--border);cursor:pointer;text-align:left;">
          <span style="font-size:20px;">🪪</span>
          <div>
            <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;">Card</p>
            <p style="font-size:11px;color:var(--ink-soft);margin:0;">Legacy card</p>
          </div>
        </button>
      </div>
    </div>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: WALLET                                                    --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @elseif($tab === 'wallet')
  <div class="anim-fade-up space-y-4" style="padding:12px 16px;" x-data="{ open: $wire.entangle('showHistory') }">

    {{-- Balance Card --}}
    <section style="text-align:center;padding:24px 16px;border-radius:var(--radius-md);
                    background:white;border:1px solid var(--border);">
      <div style="width:48px;height:48px;border-radius:50%;margin:0 auto;
                  background:var(--gold-pale);display:flex;align-items:center;
                  justify-content:center;font-size:20px;color:var(--teal-dk);">✦</div>
      <p style="font-family:var(--font-display);font-size:3rem;line-height:1;
               color:var(--gold);margin:12px 0 4px;">{{ number_format($this->coinsBalance) }}</p>
      <p style="font-size:11px;font-weight:600;text-transform:uppercase;
               letter-spacing:2px;color:var(--ink-soft);">Jannah Coins</p>
    </section>

    {{-- Referral Section --}}
    <section style="padding:16px;border-radius:var(--radius-md);background:var(--ivory);
                    border:1px solid rgba(200,168,75,0.2);">
      <h2 style="font-size:14px;font-weight:600;color:var(--ink);margin:0 0 4px;">
        Invite a Sister, Earn 25 Coins
      </h2>
      <p style="font-size:12px;color:var(--ink-soft);margin:0 0 12px;">
        When a sister joins using your link, you both benefit
      </p>
      <div style="padding:8px 12px;border-radius:6px;font-size:13px;color:var(--ink);
                  border:1px solid var(--border);background:white;overflow:hidden;">
        <p style="margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ $this->referralLink }}
        </p>
      </div>
      <div style="margin-top:12px;" x-data="{ copied: false }">
        <button
          type="button"
          x-on:click="navigator.clipboard.writeText('{{ $this->referralLink }}'); copied = true; setTimeout(() => copied = false, 2000)"
          x-text="copied ? 'Copied! ✓' : 'Copy Link'"
          style="font-family:var(--font-body);font-size:13px;font-weight:600;
                 padding:8px 20px;border-radius:20px;border:1px solid var(--teal);
                 cursor:pointer;transition:all 0.2s;
                 background:var(--teal-lt);color:var(--teal);">
        </button>
      </div>
      <p style="font-size:13px;color:var(--ink-soft);margin:12px 0 0;">
        {{ $this->referralCount }} sister(s) joined with your link
      </p>
    </section>

    {{-- Redeem Section --}}
    <section>
      <h2 style="font-size:14px;font-weight:600;color:var(--ink);margin:0 0 10px;">Redeem Your Coins</h2>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
        @foreach (range(1, 3) as $placeholder)
          <div style="display:flex;flex-direction:column;align-items:center;
                      justify-content:center;min-height:96px;border-radius:8px;
                      border:1px dashed var(--border);opacity:0.5;">
            <p style="font-size:12px;font-style:italic;color:var(--ink-soft);margin:0;">
              Coming soon
            </p>
          </div>
        @endforeach
      </div>
      <p style="font-size:12px;color:var(--ink-soft);text-align:center;margin:8px 0 0;">
        Rewards catalog launching soon — keep earning, insha'Allah ✧
      </p>
    </section>

    {{-- History --}}
    <section style="padding:16px;border-radius:var(--radius-md);background:white;
                    border:1px solid var(--border);">
      <button type="button" wire:click="toggleHistory"
              style="display:flex;align-items:center;justify-content:space-between;
                     width:100%;border:none;background:none;cursor:pointer;padding:0;">
        <span style="font-size:14px;font-weight:600;color:var(--ink);">View History</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5"
             style="width:20px;height:20px;color:var(--ink-soft);transition:transform 0.2s;"
             x-bind:style="open ? 'transform:rotate(180deg)' : ''">
          <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
      </button>

      <div x-show="open" x-transition x-cloak style="margin-top:16px;">
        <div style="display:grid;grid-template-columns:1.2fr 1.3fr 0.7fr;gap:12px;
                    padding-bottom:8px;border-bottom:1px solid var(--border);
                    font-size:12px;color:var(--ink-soft);">
          <span>Date</span>
          <span>Reason</span>
          <span style="text-align:right;">Amount</span>
        </div>
        @foreach ($this->history as $row)
          <div style="display:grid;grid-template-columns:1.2fr 1.3fr 0.7fr;gap:12px;
                      padding:8px 0;font-size:12px;color:var(--ink);">
            <span>{{ $row->created_at->format('d M Y') }}</span>
            <span>{{ match ($row->reason) {
                'onboarding' => 'Welcome gift',
                'referral' => 'Referral bonus',
                'manual' => 'Admin award',
                'admin_adjustment' => 'Adjustment',
                default => ucfirst(str_replace('_', ' ', $row->reason)),
            } }}</span>
            <span style="text-align:right;font-weight:600;
                         color:{{ $row->amount >= 0 ? '#16A34A' : '#DC2626' }};">
              {{ $row->amount >= 0 ? '+' : '' }}{{ $row->amount }}
            </span>
          </div>
        @endforeach
        <div style="margin-top:12px;">{{ $this->history->links() }}</div>
      </div>
    </section>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: MEMBERSHIP CARD                                           --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @elseif($tab === 'membership')
  <div class="anim-fade-up" style="padding:16px;">

    {{-- Badges --}}
    <div style="margin-bottom:20px;">
      <p class="section-label" style="padding:0;margin-bottom:10px;">Badges</p>
      @if ($this->badges->isNotEmpty())
        <div style="display:flex;overflow-x:auto;gap:16px;padding-bottom:4px;scrollbar-width:none;">
          @foreach ($this->badges as $userBadge)
            <div style="width:64px;text-align:center;flex-shrink:0;">
              @if ($userBadge->badge?->icon_path)
                <img src="{{ Storage::url($userBadge->badge->icon_path) }}"
                     alt="{{ $userBadge->badge->name }}"
                     style="width:40px;height:40px;object-fit:contain;margin:0 auto;">
              @else
                <div style="width:40px;height:40px;border-radius:50%;
                            background:var(--gold-pale);display:flex;
                            align-items:center;justify-content:center;
                            margin:0 auto;font-size:18px;color:var(--gold);">
                  ✦
                </div>
              @endif
              <p style="margin-top:6px;font-size:10px;color:var(--ink-soft);line-height:1.2;">
                {{ $userBadge->badge?->name }}
              </p>
            </div>
          @endforeach
        </div>
      @else
        <p style="font-size:0.875rem;font-weight:300;color:var(--ink-soft);">
          Badges will appear here as you earn them
        </p>
      @endif
    </div>

    {{-- Legacy Card --}}
    <div style="text-align:center;">
      <p class="section-label" style="padding:0;margin-bottom:12px;">Legacy Card</p>
      <div class="legacy-card-preview">
        <div style="background:var(--teal-dk);padding:20px 16px;text-align:center;">
          <img src="{{ asset('images/img1.png') }}"
               alt="TMC"
               style="width:28px;height:28px;object-fit:contain;margin:0 auto 10px;display:block;">
          <p style="font-family:var(--font-display);font-size:1.1rem;
                    color:white;margin-bottom:6px;
                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $displayName }}
          </p>
          <p style="font-family:var(--font-display);font-size:0.85rem;
                    color:var(--gold);">
            {{ number_format($this->coinsBalance) }} coins
          </p>
        </div>
      </div>
      <a href="{{ route('profile.legacy-card') }}"
         style="display:inline-block;margin-top:10px;font-family:var(--font-body);
                font-size:13px;font-weight:500;color:var(--teal);text-decoration:none;">
        View Full Card →
      </a>
    </div>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: NOTIFICATIONS CENTER                                      --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @elseif($tab === 'notifications')
  <div class="anim-fade-up" style="padding:12px 16px;">

    {{-- Notification Preferences Link --}}
    <div style="margin-bottom:16px;">
      <a href="{{ route('profile.notifications') }}"
         style="display:flex;align-items:center;justify-content:space-between;
                padding:12px 16px;border-radius:var(--radius-md);background:var(--teal-lt);
                text-decoration:none;">
        <span style="font-size:13px;font-weight:500;color:var(--teal);">
          ⚙️ Notification Preferences
        </span>
        <span style="color:var(--teal);font-size:16px;">›</span>
      </a>
    </div>

    {{-- Notifications List --}}
    @php($notifications = $this->notifications)
    @if($notifications->count() > 0)
      <div style="display:flex;flex-direction:column;gap:8px;">
        @foreach($notifications as $notification)
          <div style="padding:12px 14px;border-radius:var(--radius-md);background:white;
                      border:1px solid {{ $notification->read_at ? 'var(--border)' : 'var(--teal)' }};">
            <div style="display:flex;align-items:flex-start;gap:10px;">
              @if(!$notification->read_at)
                <span style="width:8px;height:8px;border-radius:50%;
                             background:var(--teal);flex-shrink:0;margin-top:6px;"></span>
              @endif
              <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;
                          overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  {{ $notification->data['title'] ?? $notification->type }}
                </p>
                @if(isset($notification->data['body']))
                  <p style="font-size:12px;color:var(--ink-soft);margin:4px 0 0;
                            overflow:hidden;text-overflow:ellipsis;display:-webkit-box;
                            -webkit-line-clamp:2;-webkit-box-orient:vertical;">
                    {{ $notification->data['body'] }}
                  </p>
                @endif
                <p style="font-size:11px;color:var(--ink-soft);margin:6px 0 0;">
                  {{ $notification->created_at->diffForHumans() }}
                </p>
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <div style="margin-top:12px;">{{ $notifications->links() }}</div>
    @else
      <div style="text-align:center;padding:40px 20px;">
        <p style="font-size:40px;margin:0 0 8px;">🔔</p>
        <p style="font-size:14px;font-weight:500;color:var(--ink);margin:0;">No notifications yet</p>
        <p style="font-size:13px;color:var(--ink-soft);margin:6px 0 0;">
          Updates and announcements will appear here
        </p>
      </div>
    @endif
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: REFERRALS                                                 --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @elseif($tab === 'referrals')
  <div class="anim-fade-up" style="padding:12px 16px;">

    {{-- Referral Stats --}}
    <section style="text-align:center;padding:20px 16px;border-radius:var(--radius-md);
                    background:var(--ivory);border:1px solid rgba(200,168,75,0.2);
                    margin-bottom:16px;">
      <p style="font-family:var(--font-display);font-size:2rem;color:var(--gold);margin:0;">
        {{ $this->referralCount }}
      </p>
      <p style="font-size:12px;font-weight:600;text-transform:uppercase;
               letter-spacing:1.5px;color:var(--ink-soft);margin:4px 0 0;">
        Sisters Joined
      </p>
    </section>

    {{-- Share Link --}}
    <section style="padding:16px;border-radius:var(--radius-md);background:white;
                    border:1px solid var(--border);margin-bottom:16px;">
      <h3 style="font-size:14px;font-weight:600;color:var(--ink);margin:0 0 4px;">
        Share Your Link
      </h3>
      <p style="font-size:12px;color:var(--ink-soft);margin:0 0 12px;">
        Earn 25 coins for each sister who joins
      </p>
      <div style="padding:8px 12px;border-radius:6px;font-size:13px;color:var(--ink);
                  border:1px solid var(--border);background:var(--ivory);overflow:hidden;">
        <p style="margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          {{ $this->referralLink }}
        </p>
      </div>
      <div style="margin-top:12px;" x-data="{ copied: false }">
        <button
          type="button"
          x-on:click="navigator.clipboard.writeText('{{ $this->referralLink }}'); copied = true; setTimeout(() => copied = false, 2000)"
          x-text="copied ? 'Copied! ✓' : 'Copy Referral Link'"
          style="font-family:var(--font-body);font-size:13px;font-weight:600;
                 padding:10px 20px;border-radius:20px;border:1px solid var(--teal);
                 cursor:pointer;transition:all 0.2s;width:100%;
                 background:var(--teal-lt);color:var(--teal);">
        </button>
      </div>
    </section>

    {{-- Referral List --}}
    @php($referrals = $this->referrals)
    @if($referrals->count() > 0)
      <div>
        <p class="section-label" style="padding:0;margin-bottom:10px;">Your Referrals</p>
        <div style="display:flex;flex-direction:column;gap:8px;">
          @foreach($referrals as $referral)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;
                        border-radius:var(--radius-md);background:white;
                        border:1px solid var(--border);">
              <div style="width:36px;height:36px;border-radius:50%;
                          background:var(--teal-lt);display:flex;
                          align-items:center;justify-content:center;
                          font-size:14px;font-weight:700;color:var(--teal);flex-shrink:0;">
                {{ strtoupper(mb_substr($referral->referred?->name ?? '?', 0, 1)) }}
              </div>
              <div style="flex:1;min-width:0;">
                <p style="font-size:13px;font-weight:600;color:var(--ink);margin:0;
                          overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  {{ $referral->referred?->name ?? 'Unknown' }}
                </p>
                <p style="font-size:11px;color:var(--ink-soft);margin:2px 0 0;">
                  Joined {{ $referral->created_at->diffForHumans() }}
                </p>
              </div>
              <span style="font-size:12px;font-weight:600;color:#16A34A;">+25</span>
            </div>
          @endforeach
        </div>
      </div>
    @else
      <div style="text-align:center;padding:20px;">
        <p style="font-size:40px;margin:0 0 8px;">🤝</p>
        <p style="font-size:14px;font-weight:500;color:var(--ink);margin:0;">No referrals yet</p>
        <p style="font-size:13px;color:var(--ink-soft);margin:6px 0 0;">
          Share your link to invite sisters
        </p>
      </div>
    @endif
  </div>

  {{-- ═══════════════════════════════════════════════════════════════ --}}
  {{-- TAB: SETTINGS                                                  --}}
  {{-- ═══════════════════════════════════════════════════════════════ --}}
  @elseif($tab === 'settings')
  <div class="anim-fade-up" style="padding:12px 16px;">
    <div style="border-radius:var(--radius-md);background:white;
                border:1px solid var(--border);overflow:hidden;">
      <a href="{{ route('profile.edit') }}"
         style="display:flex;align-items:center;justify-content:space-between;
                border-bottom:1px solid var(--border);padding:14px 16px;
                font-size:14px;color:var(--ink);text-decoration:none;">
        <span>Edit Profile</span>
        <span style="color:var(--teal);font-size:18px;line-height:1;">›</span>
      </a>
      <a href="{{ route('profile.notifications') }}"
         style="display:flex;align-items:center;justify-content:space-between;
                border-bottom:1px solid var(--border);padding:14px 16px;
                font-size:14px;color:var(--ink);text-decoration:none;">
        <span>Notification Preferences</span>
        <span style="color:var(--teal);font-size:18px;line-height:1;">›</span>
      </a>
      <a href="{{ route('password.change') }}"
         style="display:flex;align-items:center;justify-content:space-between;
                border-bottom:1px solid var(--border);padding:14px 16px;
                font-size:14px;color:var(--ink);text-decoration:none;">
        <span>Change Password</span>
        <span style="color:var(--teal);font-size:18px;line-height:1;">›</span>
      </a>
      <a href="{{ route('profile.legacy-card') }}"
         style="display:flex;align-items:center;justify-content:space-between;
                border-bottom:1px solid var(--border);padding:14px 16px;
                font-size:14px;color:var(--ink);text-decoration:none;">
        <span>Legacy Card</span>
        <span style="color:var(--teal);font-size:18px;line-height:1;">›</span>
      </a>
      <form method="POST" action="{{ route('logout') }}" style="padding:14px 16px;">
        @csrf
        <button type="submit"
                style="font-size:14px;color:var(--ink);
                       background:none;border:none;cursor:pointer;padding:0;">
          Logout
        </button>
      </form>
    </div>
  </div>
  @endif

</div>
