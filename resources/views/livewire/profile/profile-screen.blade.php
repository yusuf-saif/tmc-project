@php($roleBadge = $this->roleBadge())
@php($displayName = $this->profile?->display_name ?: auth()->user()->name)

<div class="anim-fade-in">

  {{-- Banner --}}
  <div class="profile-banner"></div>

  {{-- Avatar + name --}}
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

  {{-- Stats --}}
  <div class="profile-stats anim-fade-up delay-1">
    <div class="profile-stat-card">
      <p class="profile-stat-val">{{ $this->memberSince }}</p>
      <p class="profile-stat-lbl">Since</p>
    </div>
    <a href="{{ route('wallet') }}" class="profile-stat-card"
       style="text-decoration:none;">
      <p class="profile-stat-val">{{ number_format($this->coinsBalance) }}</p>
      <p class="profile-stat-lbl">Coins</p>
    </a>
    <a href="#badges" class="profile-stat-card"
       style="text-decoration:none;">
      <p class="profile-stat-val">{{ $this->badges->count() }}</p>
      <p class="profile-stat-lbl">Badges</p>
    </a>
  </div>

  {{-- Interests --}}
  @if($this->interests->isNotEmpty())
  <div class="anim-fade-up delay-2" style="padding:0 16px 16px;">
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

  {{-- Badges --}}
  <div id="badges" class="anim-fade-up delay-2"
       style="padding:0 16px 20px;">
    <p class="section-label" style="padding:0;margin-bottom:10px;">Badges</p>
    @if ($this->badges->isNotEmpty())
      <div style="display:flex;overflow-x:auto;gap:16px;
                  padding-bottom:4px;scrollbar-width:none;">
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
            <p style="margin-top:6px;font-size:10px;color:var(--ink-soft);
                      line-height:1.2;">
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

  {{-- Legacy Card preview --}}
  <div class="anim-fade-up delay-3"
       style="padding:0 16px 16px;text-align:center;">
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
       style="font-family:var(--font-body);font-size:13px;font-weight:500;
              color:var(--teal);text-decoration:none;">
      View Full Card →
    </a>
  </div>

  {{-- Settings menu --}}
  <div class="anim-fade-up delay-4"
       style="margin:0 16px 16px;border-radius:var(--radius-md);
              background:white;border:1px solid var(--border);
              overflow:hidden;">
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
    <a href="/password/edit"
       style="display:flex;align-items:center;justify-content:space-between;
              border-bottom:1px solid var(--border);padding:14px 16px;
              font-size:14px;color:var(--ink);text-decoration:none;">
      <span>Change Password</span>
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
