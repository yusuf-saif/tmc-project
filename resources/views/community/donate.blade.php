@component('layouts.app', ['title' => 'Support TMC'])
    <div class="space-y-6">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">Support TMC</h1>
        <p class="text-center font-arabic text-[1.5rem]" style="color: rgba(200, 168, 75, 0.5);">بارك الله فيكم</p>

        <section class="rounded-[8px] p-6" style="background: var(--ivory); border: 1px solid var(--border);">
            <p class="text-sm font-light leading-8 text-ink-md">{{ $donateMessage }}</p>
        </section>

        <section class="rounded-[8px] bg-white p-6" style="border-left: 3px solid var(--gold); border-top: 1px solid var(--border); border-right: 1px solid var(--border); border-bottom: 1px solid var(--border);">
            <h2 class="text-sm font-semibold text-ink">Bank Transfer Details</h2>
            <pre class="mt-3 whitespace-pre-wrap font-body text-sm font-light leading-7 text-ink-md">{{ $bankDetails }}</pre>
        </section>

        <p class="text-center text-[12px] font-light italic text-ink-soft">Every contribution, however small, is deeply appreciated.</p>
    </div>
@endcomponent
