<div class="space-y-6" x-data="{ open: $wire.entangle('showHistory') }">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">My Wallet</h1>
    </section>

    <section class="rounded-[12px] bg-white p-6 text-center" style="border: 1px solid var(--border);">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl text-teal-dk" style="background: var(--gold-pale);">✦</div>
        <p class="mt-4 font-display text-[4rem] leading-none text-gold" wire:key="wallet-balance-{{ $this->balance }}">{{ $this->balance }}</p>
        <p class="mt-3 text-[11px] uppercase tracking-[2px] text-ink-soft">Jannah Coins</p>
    </section>

    <section class="rounded-[12px] p-5" style="background: var(--ivory); border: 1px solid rgba(200, 168, 75, 0.2);">
        <h2 class="text-sm font-semibold text-ink">Invite a Sister, Earn 25 Coins</h2>
        <p class="mt-2 text-[12px] font-light leading-6 text-ink-soft">When a sister joins using your link, you both benefit</p>

        <div class="mt-4 rounded-[6px] px-3 py-2 text-[13px] text-ink" style="border: 1px solid var(--border); background: var(--ivory);">
            <p class="truncate">{{ $this->referralLink }}</p>
        </div>

        <div class="mt-4" x-data="{ copied: false }">
            <button
                type="button"
                class="tmc-button-outline max-w-[180px]"
                x-bind:class="copied ? 'bg-teal text-white border-teal' : ''"
                x-on:click="navigator.clipboard.writeText('{{ $this->referralLink }}'); copied = true; setTimeout(() => copied = false, 2000)"
                x-text="copied ? 'Copied! ✓' : 'Copy Link'"
            ></button>
        </div>

        <p class="mt-4 text-sm font-light leading-7 text-ink-soft">{{ $this->referralCount }} sister(s) joined with your link</p>
    </section>

    <section class="space-y-4 rounded-[12px] p-5" style="background: var(--mint-lt); border: 1px solid var(--border);">
        <h2 class="text-sm font-semibold text-ink">Your Jannah Coins Are Worth ₦{{ number_format(($this->balance * App\Services\CoinsService::coinValueKobo()) / 100) }}</h2>
        <p class="text-[12px] font-light leading-6 text-ink-soft">
            Apply them as a discount on your membership renewal or Souq listing fee —
            up to {{ App\Services\CoinsService::maxRedemptionPercent() }}% off any payment.
        </p>
    </section>

    <section class="space-y-4 rounded-[12px] bg-white p-5" style="border: 1px solid var(--border);">
        <button type="button" wire:click="toggleHistory" class="flex w-full items-center justify-between text-left">
            <span class="text-sm font-semibold text-ink">View History</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5 text-ink-soft transition" x-bind:class="open ? 'rotate-180' : ''"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
        </button>

        <div x-show="open" x-transition x-cloak class="space-y-4">
            <div class="grid grid-cols-[1.2fr_1.3fr_0.7fr] gap-3 border-b pb-2 text-[12px] font-light text-ink-soft" style="border-color: var(--border);">
                <span>Date</span>
                <span>Reason</span>
                <span class="text-right">Amount</span>
            </div>

            @foreach ($this->history as $row)
                <div class="grid grid-cols-[1.2fr_1.3fr_0.7fr] gap-3 text-[12px] text-ink-md">
                    <span>{{ $row->created_at->hijri('d M Y') }}</span>
                    <span>{{ match ($row->reason) {
                        'onboarding' => 'Welcome gift',
                        'referral' => 'Referral bonus',
                        'manual' => 'Admin award',
                        'admin_adjustment' => 'Adjustment',
                        'welcome' => 'Welcome bonus',
                        'badge_reward' => 'Badge reward',
                        'redemption_membership' => 'Membership discount',
                        'redemption_souq' => 'Souq fee discount',
                        default => ucfirst(str_replace('_', ' ', $row->reason)),
                    } }}</span>
                    <span class="text-right font-semibold {{ $row->amount >= 0 ? 'text-green-600' : 'text-red-500' }}">{{ $row->amount >= 0 ? '+' : '' }}{{ $row->amount }}</span>
                </div>
            @endforeach

            <div>
                {{ $this->history->links() }}
            </div>
        </div>
    </section>
</div>
