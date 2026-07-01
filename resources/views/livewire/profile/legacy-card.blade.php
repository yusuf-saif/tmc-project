@php($displayName = auth()->user()->profile?->display_name ?? auth()->user()->name)
@php($goldGradient = 'linear-gradient(135deg, #C8A84B 35%, #DCC182 100%)')

<div class="flex min-h-[calc(100vh-3.5rem-1rem)] flex-col items-center justify-center py-8">
    <div class="w-full text-center" style="max-width:min(340px, calc(100vw - 2rem)); margin:auto; filter: drop-shadow(0 0 40px rgba(200,168,75,0.2));">
        <div class="relative overflow-hidden rounded-[16px] border px-6 py-10 text-center" style="background: linear-gradient(180deg, #fbfaf6 0%, #f3efe6 100%); border-color: rgba(200,168,75,0.55);">
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image: url('/images/img4.png'); background-size: 300px; border-radius: inherit;"></div>
            <div class="relative z-10">
                <img src="{{ asset('images/img1.png') }}" alt="TMC" class="mx-auto mb-4 h-[52px] w-[52px] object-contain">

                <div class="mx-auto mb-4 h-[1.5px] w-10 rounded-full" style="background: linear-gradient(90deg, transparent, #C8A84B, transparent);"></div>

                <p dir="rtl" style="font-family:'Amiri',serif; font-weight:700; font-size:2.8rem; direction:rtl; background: {{ $goldGradient }}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height:1.2; letter-spacing:0.02em; filter: drop-shadow(0 1px 3px rgba(200,168,75,0.2));">المحسنات</p>

                <div class="mx-auto mb-5 mt-3 h-[1.5px] w-10 rounded-full" style="background: linear-gradient(90deg, transparent, #C8A84B, transparent);"></div>

                <p class="font-display text-[1.6rem] font-semibold" style="color:#0f6b73; letter-spacing:0.01em;">{{ $displayName }}</p>
                <p class="mt-2 text-[11px] uppercase tracking-[2px] font-medium" style="color:#4a6361;">TMC Member</p>
                <p class="mb-5 mt-2 text-[12px] font-light" style="color:#5f7876;">Member since {{ $this->memberSince }}</p>

                <div class="h-[1px] w-full" style="background: linear-gradient(90deg, transparent, rgba(200,168,75,0.25), transparent);"></div>

                <div class="mt-4 flex items-center justify-center gap-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full" style="background: {{ $goldGradient }}; color: #fff; box-shadow: 0 2px 8px rgba(200,168,75,0.35); font-size:14px; line-height:1;">✦</span>
                    <span style="font-family:'Amiri',serif; font-size:1.8rem; font-weight:700; background: {{ $goldGradient }}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; filter: drop-shadow(0 1px 2px rgba(200,168,75,0.2));">{{ $this->coinsBalance }}</span>
                    <span class="text-[11px] font-medium" style="color:#4a6361;">Jannah Coins</span>
                </div>

                <div class="mx-auto mt-6 flex items-center justify-center rounded-xl border p-3" style="border-color: rgba(15,107,115,0.2); box-shadow: 0 0 16px rgba(15,107,115,0.1); max-width: 140px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(url()->current()) }}" alt="QR Code" class="h-[90px] w-[90px]">
                </div>
                <p class="mt-1 text-[10px] uppercase tracking-[1.5px] font-medium" style="color:#5f7876;">Scan to verify</p>
            </div>
        </div>

        <button
            type="button"
            class="mt-6 inline-flex min-h-11 w-full max-w-[200px] items-center justify-center border px-5 text-[12px] font-semibold uppercase tracking-[1.5px] text-white transition-all duration-200 active:scale-[0.97]"
            style="background: linear-gradient(135deg, #0f6b73, #0b4f56); border-color: rgba(200,168,75,0.4); border-radius: 6px; box-shadow: 0 6px 20px rgba(15, 107, 115, 0.25);"
            x-data
            x-on:click="if (navigator.share) { navigator.share({ title: 'The Muhsinat Club', text: 'I am a member of The Muhsinat Club', url: window.location.href }) } else { alert('Screenshot this card to share it, insha\'Allah ✧') }"
        >Share My Card</button>

        <div class="mt-4">
            <a href="{{ route('profile') }}" class="text-[13px] no-underline transition-opacity duration-150 hover:opacity-70" style="color:#0f6b73;">&larr; Back to Profile</a>
        </div>
    </div>
</div>
