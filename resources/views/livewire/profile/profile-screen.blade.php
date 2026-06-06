@php($roleBadge = $this->roleBadge())
@php($displayName = $this->profile?->display_name ?: auth()->user()->name)

<div class="space-y-8 px-4 pb-4">
    <section class="text-center">
        <div style="width:100%; height:110px; background: #1A6B72;
                    background-image: url('/images/img4.png');
                    background-size: 300px; background-repeat: repeat;
                    position: relative;"></div>
        <div class="space-y-4">
        @if ($this->profile?->avatar_path)
            <img src="{{ Storage::url($this->profile->avatar_path) }}" alt="{{ $displayName }}"
                 style="width:80px; height:80px; border-radius:50%;
                        border: 3px solid white; margin: -40px auto 0;
                        display:block; object-fit:cover;
                        position: relative; z-index: 1;">
        @else
            <div style="width:80px; height:80px; border-radius:50%;
                        border: 3px solid white; margin: -40px auto 0;
                        display: flex; align-items:center; justify-content:center;
                        background: #2A8A93; position: relative; z-index: 1;">
                <span class="font-display text-[2rem] leading-none text-white">{{ strtoupper(mb_substr($displayName, 0, 1)) }}</span>
            </div>
        @endif
        <div>
            <h1 class="font-display text-[1.8rem] leading-none text-teal-dk">{{ $displayName }}</h1>
            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[1px]" style="background: var(--teal); color: white;">{{ $roleBadge['label'] }}</span>
        </div>
        </div>
    </section>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-[8px] bg-white p-4 text-center" style="border: 1px solid var(--border);">
            <p class="text-[11px] font-light uppercase tracking-[0.6px] text-ink-soft">Member Since</p>
            <p class="mt-2 text-[1.1rem] font-bold text-teal">{{ $this->memberSince }}</p>
        </div>
        <a href="{{ route('wallet') }}" class="rounded-[8px] bg-white p-4 text-center no-underline" style="border: 1px solid var(--border);">
            <p class="text-[11px] font-light uppercase tracking-[0.6px] text-ink-soft">Jannah Coins</p>
            <p class="mt-2 text-[1.1rem] font-bold text-teal">{{ $this->coinsBalance }}</p>
        </a>
        <a href="#badges" class="rounded-[8px] bg-white p-4 text-center no-underline" style="border: 1px solid var(--border);">
            <p class="text-[11px] font-light uppercase tracking-[0.6px] text-ink-soft">Badges</p>
            <p class="mt-2 text-[1.1rem] font-bold text-teal">{{ $this->badges->count() }}</p>
        </a>
    </section>

    <section class="space-y-3">
        <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">Interests</p>
        <div class="-mx-4 overflow-x-auto px-4">
            <div class="flex min-w-max gap-2">
                @foreach ($this->interests as $interest)
                    <span class="inline-flex rounded-full px-3 py-1.5 text-[12px] text-teal" style="background: var(--teal-lt);">{{ $interest->name }}</span>
                @endforeach
            </div>
        </div>
    </section>

    <section id="badges" class="space-y-3">
        <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">Badges</p>
        @if ($this->badges->isNotEmpty())
            <div class="-mx-4 overflow-x-auto px-4">
                <div class="flex min-w-max gap-4">
                    @foreach ($this->badges as $userBadge)
                        <div class="w-16 text-center">
                            @if ($userBadge->badge?->icon_path)
                                <img src="{{ Storage::url($userBadge->badge->icon_path) }}" alt="{{ $userBadge->badge->name }}" class="mx-auto h-10 w-10 object-contain">
                            @else
                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-gold-pale text-gold">✦</div>
                            @endif
                            <p class="mt-2 text-[10px] text-ink-soft">{{ $userBadge->badge?->name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-sm font-light text-ink-soft">Badges will appear here as you earn them</p>
        @endif
    </section>

    <section class="space-y-4 text-center">
        <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">Legacy Card</p>
        <div style="max-width: 200px; margin: 0 auto 16px; border-radius: 12px;
                    overflow: hidden; box-shadow: 0 4px 20px rgba(26,107,114,0.2);">
            <div class="p-4 text-white" style="background: var(--teal-dk);">
                <img src="{{ asset('images/img1.png') }}" alt="TMC" class="mx-auto h-6 w-6 object-contain">
                <p class="mt-3 truncate font-display text-[0.9rem]">{{ $displayName }}</p>
                <p class="mt-2 font-display text-[0.8rem] text-gold">{{ $this->coinsBalance }} coins</p>
            </div>
        </div>
        <a href="{{ route('profile.legacy-card') }}"
           style="display:block; text-align:center; font-family:'Nunito',sans-serif;
                  font-size:13px; font-weight:500; color:#1A6B72; text-decoration:none;">
            View Full Card →
        </a>
    </section>

    <section class="overflow-hidden rounded-[12px] bg-white" style="border: 1px solid var(--border);">
        <a href="{{ route('profile.edit') }}" class="flex items-center justify-between border-b px-4 py-4 text-[14px] text-ink no-underline opacity-100 transition hover:opacity-70" style="border-color: var(--border);"><span>Edit Profile</span><span class="text-teal">›</span></a>
        <a href="{{ route('profile.notifications') }}" class="flex items-center justify-between border-b px-4 py-4 text-[14px] text-ink no-underline opacity-100 transition hover:opacity-70" style="border-color: var(--border);"><span>Notification Preferences</span><span class="text-teal">›</span></a>
        <a href="/password/edit" class="flex items-center justify-between border-b px-4 py-4 text-[14px] text-ink no-underline opacity-100 transition hover:opacity-70" style="border-color: var(--border);"><span>Change Password</span><span class="text-teal">›</span></a>
        <form method="POST" action="{{ route('logout') }}" class="px-4 py-4">
            @csrf
            <button type="submit" class="text-[14px] text-ink opacity-100 transition hover:opacity-70">Logout</button>
        </form>
    </section>
</div>
