<div class="anim-fade-in">

  {{-- Greeting card --}}
  <div class="greeting-card anim-fade-up">
    <div class="greeting-card__bg"></div>
    <div class="greeting-card__overlay"></div>
    <div class="greeting-card__content">
      <p class="greeting-title">{{ $greeting }}</p>
      <p class="greeting-subtitle">{{ $dailyPhrase }}</p>
    </div>
  </div>

  {{-- Free-plan banner --}}
  @if($onboardingStatus === 'active')
  <div x-data="{ dismissed: false }" x-show="!dismissed"
       x-transition.opacity.duration.300ms
       style="margin-bottom:12px;background:var(--gold);border-radius:10px;padding:12px 14px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:16px;">✦</span>
    <p style="flex:1;font-size:13px;font-weight:500;color:var(--teal-dk);line-height:1.4;">
      You're on a free plan —
      <a href="{{ route('membership.payment') }}" style="color:var(--teal-dk);font-weight:700;text-decoration:underline;">
        upgrade to unlock full access →</a>
    </p>
    <button @click="dismissed = true"
            style="background:none;border:none;color:var(--teal-dk);font-size:18px;cursor:pointer;padding:0;line-height:1;">
      ×
    </button>
  </div>
  @endif

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
      <span class="support-cta-text">Support our sisterhood →</span>
    </a>
  </div>

</div>
