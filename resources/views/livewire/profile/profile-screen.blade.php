@php($roleBadge = $this->roleBadge())
@php($displayName = $this->profile?->display_name ?: auth()->user()->name)

<div class="space-y-8">
    <section class="space-y-4 text-center">
        @if ($this->profile?->avatar_path)
            <img src="{{ Storage::url($this->profile->avatar_path) }}" alt="{{ $displayName }}" class="mx-auto h-20 w-20 rounded-full object-cover">
        @else
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-teal text-white">
                <span class="font-display text-[2rem] leading-none">{{ strtoupper(mb_substr($displayName, 0, 1)) }}</span>
            </div>
        @endif
        <div>
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $displayName }}</h1>
            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[1px]" style="{{ $roleBadge['style'] }}">{{ $roleBadge['label'] }}</span>
        </div>
    </section>

    <section class="grid grid-cols-3 gap-3">
        <div class="rounded-[8px] bg-white p-4 text-center" style="border: 1px solid var(--border);">
            <p class="text-[12px] font-light text-ink-soft">Member Since</p>
            <p class="mt-2 text-sm font-semibold text-ink">{{ $this->memberSince }}</p>
        </div>
        <a href="{{ route('wallet') }}" class="rounded-[8px] bg-white p-4 text-center no-underline" style="border: 1px solid var(--border);">
            <p class="text-[12px] font-light text-ink-soft">Jannah Coins</p>
            <p class="mt-2 text-sm font-semibold text-gold">{{ $this->coinsBalance }}</p>
        </a>
        <a href="#badges" class="rounded-[8px] bg-white p-4 text-center no-underline" style="border: 1px solid var(--border);">
            <p class="text-[12px] font-light text-ink-soft">Badges</p>
            <p class="mt-2 text-sm font-semibold text-ink">{{ $this->badges->count() }}</p>
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
        <div class="mx-auto max-w-[200px] rounded-[8px] p-4 text-white" style="background: var(--teal-dk);">
            <img src="{{ asset('images/img1.png') }}" alt="TMC" class="mx-auto h-6 w-6 object-contain">
            <p class="mt-3 truncate font-display text-[0.9rem]">{{ $displayName }}</p>
            <p class="mt-2 font-display text-[0.8rem] text-gold">{{ $this->coinsBalance }} coins</p>
        </div>
        <a href="{{ route('profile.legacy-card') }}" class="tmc-button-outline mx-auto max-w-[200px] no-underline">View Full Card</a>
    </section>

    <section class="space-y-2">
        <a href="{{ route('profile.edit') }}" class="flex items-center justify-between py-2 text-[14px] text-teal no-underline"><span>Edit Profile</span><span>›</span></a>
        <a href="{{ route('profile.notifications') }}" class="flex items-center justify-between py-2 text-[14px] text-teal no-underline"><span>Notification Preferences</span><span>›</span></a>
        <a href="/password/edit" class="flex items-center justify-between py-2 text-[14px] text-teal no-underline"><span>Change Password</span><span>›</span></a>
        <form method="POST" action="{{ route('logout') }}" class="pt-2">
            @csrf
            <button type="submit" class="text-[14px] text-teal">Logout</button>
        </form>
    </section>
</div>
