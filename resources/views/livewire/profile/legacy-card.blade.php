@php($displayName = auth()->user()->profile->display_name ?? auth()->user()->name)

<div class="flex min-h-[calc(100vh-3.5rem-1rem)] flex-col items-center justify-center py-8">
    <div class="w-full max-w-[340px] text-center">
        <div class="relative overflow-hidden rounded-[16px] border px-6 py-10 text-center" style="background: rgba(250,248,243,0.06); border-color: rgba(200,168,75,0.3); backdrop-filter: blur(8px);">
            <div class="pointer-events-none absolute inset-0 opacity-[0.08]" style="background-image: url('/images/img4.png'); background-size: 300px; border-radius: inherit;"></div>
            <div class="relative z-10">
                <img src="{{ asset('images/img1.png') }}" alt="TMC" class="mx-auto mb-4 h-[52px] w-[52px] object-contain">
                <div class="mx-auto mb-4 h-px w-12" style="background: var(--gold); opacity: 0.6;"></div>
                <p class="font-arabic text-[2.8rem] font-bold leading-[1.2]" dir="rtl" style="color: #E8CB7A;">المحسنات</p>
                <div class="mx-auto mb-5 mt-3 h-px w-12" style="background: var(--gold); opacity: 0.6;"></div>
                <p class="font-display text-[1.6rem] text-white">{{ $displayName }}</p>
                <p class="mt-2 text-[11px] uppercase tracking-[1.5px] text-white/50">TMC Member</p>
                <p class="mb-5 mt-2 text-[12px] font-light text-white/40">Member since {{ $this->memberSince }}</p>
                <div class="h-px w-full" style="background: var(--gold); opacity: 0.15;"></div>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px]" style="background: var(--gold); color: var(--teal-dk);">✦</span>
                    <span class="font-display text-[1.4rem]" style="color: #E8CB7A;">{{ $this->coinsBalance }}</span>
                    <span class="text-[11px] text-white/45">Jannah Coins</span>
                </div>
            </div>
        </div>

        <button
            type="button"
            class="mt-6 inline-flex min-h-11 w-full max-w-[200px] items-center justify-center border border-white px-5 text-[12px] font-semibold uppercase tracking-[1px] text-white"
            style="border-radius: 2px;"
            x-data
            x-on:click="if (navigator.share) { navigator.share({ title: 'The Muhsinat Club', text: 'I am a member of The Muhsinat Club', url: window.location.href }) } else { alert('Screenshot this card to share it, insha\'Allah ✧') }"
        >Share My Card</button>

        <div class="mt-4">
            <a href="{{ route('profile') }}" class="text-[13px] text-teal-lt no-underline">&larr; Back to Profile</a>
        </div>
    </div>
</div>
