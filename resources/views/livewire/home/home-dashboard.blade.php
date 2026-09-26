<div class="anim-fade-in">

  <livewire:announcement-popup />

  {{-- Greeting card --}}
  <div class="greeting-card anim-fade-up">
    <div class="greeting-card__bg"></div>
    <div class="greeting-card__overlay"></div>
    <div class="greeting-card__content">
      <p class="greeting-title">{{ $greeting }}</p>
      <p class="greeting-subtitle">{{ $dailyPhrase }}</p>
    </div>
  </div>

  {{-- Welcome coins celebration --}}
  @if(session('welcome_coins_awarded'))
  <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.500ms
       class="anim-fade-up"
       style="margin-bottom:16px;background:linear-gradient(135deg, #F5E6C8 0%, #E8D5A3 100%);border:1px solid var(--gold);border-radius:12px;padding:20px 16px;text-align:center;">
    <div style="font-size:32px;margin-bottom:8px;">✦</div>
    <p style="font-family:'Nunito',sans-serif;font-size:18px;font-weight:700;color:var(--teal-dk);margin:0 0 4px 0;">
      You've earned {{ number_format(session('welcome_coins_awarded')) }} Jannah Coins
    </p>
    <p style="font-family:'Nunito',sans-serif;font-size:13px;color:var(--ink-soft);margin:0 0 12px 0;">
      Welcome to The Muhsinat Club!
    </p>
    <a href="{{ route('profile', ['tab' => 'wallet']) }}" wire:navigate
       style="display:inline-block;background:var(--gold);color:var(--teal-dk);padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">
      View Your Wallet →
    </a>
    <button @click="show = false"
            style="display:block;margin:12px auto 0;background:none;border:none;color:var(--ink-soft);font-size:12px;cursor:pointer;text-decoration:underline;">
      Dismiss
    </button>
  </div>
  @endif

  {{-- Free-plan banner --}}
  @if($onboardingStatus === 'active')
  <div x-data="{ dismissed: false }" x-show="!dismissed"
       x-transition.opacity.duration.300ms
       style="margin-bottom:12px;background:var(--gold);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:16px;">✦</span>
    <p style="flex:1;font-size:13px;font-weight:500;color:var(--teal-dk);line-height:1.4;">
      You're on a free plan —
      <a href="{{ route('membership.payment') }}" wire:navigate style="color:var(--teal-dk);font-weight:700;text-decoration:underline;">
        upgrade to unlock full access →</a>
    </p>
    <button @click="dismissed = true"
            style="background:none;border:none;color:var(--teal-dk);font-size:18px;cursor:pointer;padding:0;line-height:1;">
      ×
    </button>
  </div>
  @endif

  {{-- Push notification enable banner --}}
  @if(!$hasPushEnabled)
  <div x-data="{ dismissed: localStorage.getItem('tmc_push_banner_dismissed') === '1' }" x-show="!dismissed"
       x-transition.opacity.duration.300ms
       style="margin-bottom:12px;background:var(--teal);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:16px;">🔔</span>
    <p style="flex:1;font-size:13px;font-weight:500;color:white;line-height:1.4;">
      Enable notifications to stay updated on events, announcements, and more.
    </p>
    <div style="display:flex;gap:6px;flex-shrink:0;">
      <button id="push-enable-btn"
              style="background:white;color:var(--teal-dk);border:none;border-radius:6px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;">
        Enable
      </button>
      <button @click="dismissed = true; localStorage.setItem('tmc_push_banner_dismissed', '1')"
              style="background:none;border:none;color:white;font-size:18px;cursor:pointer;padding:0;line-height:1;">
        ×
      </button>
    </div>
  </div>
  @endif

  {{-- PWA install card --}}
  <div
    id="home-install-card"
    x-data="{
      show: false,
      isIos: false,
      init() {
        this.isIos = isIOS() && !isInStandaloneMode();
        this.evaluate();
        window.addEventListener('beforeinstallprompt', () => setTimeout(() => this.evaluate(), 0));
        window.addEventListener('appinstalled', () => { this.show = false; });
      },
      evaluate() {
        if (isAlreadyStandalone()) { this.show = false; return; }
        this.show = !!(window.__tmcInstall && window.__tmcInstall.available) || this.isIos;
      },
      handleClick() {
        if (this.isIos) {
          this.$dispatch('open-ios-install-instructions');
        } else {
          installPWA();
        }
      }
    }"
    x-show="show"
    x-cloak
    x-transition.opacity.duration.300ms
    style="margin-bottom:12px;background:var(--ivory);border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:16px;flex-shrink:0;">📲</span>
    <p style="flex:1;font-size:13px;font-weight:500;color:var(--teal-dk);line-height:1.4;margin:0;">
      Install The Muhsinat Club for faster, app-like access.
    </p>
    <button
      @click="handleClick()"
      class="btn btn-gold btn-sm"
      style="flex-shrink:0;">
      Install
    </button>
  </div>

  {{-- Coins card --}}

  <a href="{{ url('/profile?tab=wallet') }}" class="coins-card anim-fade-up delay-1">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="coins-icon">
        <span style="font-size:14px;color:white;">✦</span>
      </div>
      <div>
        <p class="coins-amount">{{ number_format($balance) }}</p>
        <p class="coins-label">Jannah Coins</p>
      </div>
    </div>
    <span class="coins-link">Wallet →</span>
  </a>

  {{-- Upcoming events --}}
  @if(count($events))
  <div style="margin-bottom:16px;">
    <p class="section-label anim-fade-up delay-2">Upcoming Halaqahs</p>
    @foreach($events as $i => $event)
    <div class="page-pad anim-fade-up"
         style="animation-delay:{{ 0.10 + $i * 0.06 }}s;">
      <div class="event-card">
        <div class="event-card__placeholder">
          <span>TMC</span>
        </div>
        <div class="event-card__body">
          <p class="event-card__title">{{ $event->title }}</p>
          <div class="event-card__meta">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
            </svg>
            <span>{{ \Carbon\Carbon::parse($event->event_date)->hijri('D d M · g:ia') }}</span>
          </div>
          <div class="event-card__footer">
            @php
              $locationType = $event->location_type ?? 'online';
              $badgeClass = match($locationType) {
                'in_person' => 'badge-gold',
                'hybrid'    => 'badge-plum',
                default     => 'badge-teal',
              };
              $locationLabel = ucfirst(str_replace('_', ' ', $locationType));
            @endphp
            <span class="badge {{ $badgeClass }}">{{ $locationLabel }}</span>
            <a href="{{ route('events.show', $event->slug) }}"
               class="btn btn-teal btn-sm">RSVP</a>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @else
  <div style="margin-bottom:16px;">
    <p class="section-label anim-fade-up delay-2">Upcoming Halaqahs</p>
    <p class="page-pad event-empty anim-fade-up delay-2">
      No upcoming halaqahs — check back soon
    </p>
  </div>
  @endif

  {{-- New resource --}}
  @if($newResource)
  <div style="margin-bottom:16px;">
    <p class="section-label anim-fade-up delay-2">New Resource</p>
    <div class="page-pad anim-fade-up" style="animation-delay:0.16s;">
      <a href="{{ route('resources.show', $newResource->slug) }}"
         class="flex items-center justify-between gap-4 rounded-[8px] bg-white p-4 no-underline transition hover:-translate-y-[2px] hover:shadow-sm"
         style="border:1px solid var(--border);">
        <div class="min-w-0 space-y-1">
          <p class="truncate text-sm font-semibold text-ink">{{ $newResource->title }}</p>
          <p class="line-clamp-1 text-[12px] font-light text-ink-soft">{{ $newResource->description }}</p>
        </div>
        <span class="inline-flex shrink-0 rounded-full bg-teal-lt px-2.5 py-1 text-[11px] font-medium text-teal">{{ $newResource->category?->name ?? 'Resource' }}</span>
      </a>
    </div>
  </div>
  @endif

  {{-- Quick actions --}}
  <p class="section-label anim-fade-up delay-3">Quick Actions</p>
  <div class="quick-actions anim-fade-up delay-3">

    <a href="{{ route('journal') }}" class="qa-tile">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4z"/>
      </svg>
      <span>Journal</span>
    </a>

    <a href="{{ route('journal') }}?tab=duas" class="qa-tile">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168
                 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477
                 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0
                 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5
                 18c-1.746 0-3.332.477-4.5 1.253"/>
      </svg>
      <span>Du'a</span>
    </a>

    <a href="{{ route('events') }}" class="qa-tile">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5"
           stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/>
        <line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      <span>Events</span>
    </a>

    <a href="{{ route('souq') }}" class="qa-tile">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      <span>Souq</span>
    </a>

  </div>

  {{-- Support TMC --}}
  <div class="page-pad anim-fade-up delay-4" style="margin-bottom:8px;">
    <a href="{{ route('community') }}" class="support-cta">
      <span class="support-cta-text">{{ \App\Models\Setting::get('support_banner_text') }}</span>
    </a>
  </div>

</div>

@once
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('push-enable-btn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
      if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        return;
      }

      var perm = await Notification.requestPermission();
      if (perm !== 'granted') return;

      try {
        var reg = await navigator.serviceWorker.register('/sw.js');
        var sub = await reg.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });

        await fetch('/push/subscribe', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content
          },
          body: JSON.stringify(sub)
        });

        var banner = btn.closest('[x-data]');
        if (banner && typeof Alpine !== 'undefined') {
          Alpine.$data(banner).dismissed = true;
        }
      } catch (e) {
        console.warn('Push subscription failed:', e);
      }
    });
  });
</script>
@endonce
