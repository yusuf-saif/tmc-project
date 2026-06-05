<div class="space-y-6">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">The Souq</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Muslim women-owned businesses, curated for the sisterhood</p>
    </section>

    <section class="-mx-4 overflow-x-auto px-4">
        <div class="flex min-w-max items-center gap-5 border-b" style="border-color: var(--border);">
            @foreach (['all' => 'All', 'fashion' => 'Fashion', 'food_catering' => 'Food & Catering', 'health_beauty' => 'Health & Beauty', 'education' => 'Education', 'services' => 'Services', 'creative' => 'Creative', 'other' => 'Other'] as $value => $label)
                <button type="button" wire:click="setCategory('{{ $value }}')" class="border-b-2 pb-3 text-[13px] font-semibold uppercase tracking-[1.2px] transition" style="border-color: {{ $category === $value ? '#1A6B72' : 'transparent' }}; color: {{ $category === $value ? '#1A6B72' : '#6B6760' }}; border-radius: 0;">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </section>

    <div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search businesses..." class="w-full bg-white px-4 py-3 text-[14px] text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;">
    </div>

    <div class="flex justify-end">
        <a href="{{ route('souq.apply') }}" class="tmc-button-gold max-w-[220px] no-underline">List My Business</a>
    </div>

    @if ($this->souqListings->count())
        <section class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach ($this->souqListings as $listing)
                @php
                    $badge = match ($listing->category) {
                        'fashion' => ['bg' => 'var(--plum-lt)', 'text' => '#3D1A47'],
                        'food_catering' => ['bg' => 'var(--gold-pale)', 'text' => 'var(--gold)'],
                        'health_beauty' => ['bg' => 'var(--teal-lt)', 'text' => 'var(--teal)'],
                        'education' => ['bg' => 'var(--mint-lt)', 'text' => 'var(--teal)'],
                        'services' => ['bg' => 'var(--ivory)', 'text' => 'var(--ink-soft)'],
                        'creative' => ['bg' => 'var(--plum-lt)', 'text' => '#3D1A47'],
                        default => ['bg' => '#F1F5F9', 'text' => 'var(--ink-soft)'],
                    };
                @endphp
                <a href="{{ route('souq.show', $listing->slug) }}" class="rounded-[8px] bg-white p-4 no-underline" style="border: 1px solid var(--border);">
                    <div class="flex justify-center">
                        @if ($listing->logo_path)
                            <img src="{{ Storage::url($listing->logo_path) }}" alt="{{ $listing->business_name }}" class="h-16 w-16 rounded-full border object-contain" style="border-color: var(--border);">
                        @else
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-teal text-white">
                                <span class="font-display text-[1.5rem] leading-none">{{ strtoupper(mb_substr($listing->business_name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 space-y-2 text-center">
                        <h2 class="text-sm font-semibold leading-6 text-ink">{{ $listing->business_name }}</h2>
                        <div>
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium" style="background: {{ $badge['bg'] }}; color: {{ $badge['text'] }};">
                                {{ $listing->categoryLabel() }}
                            </span>
                        </div>
                        <p class="line-clamp-2 text-[12px] font-light leading-6 text-ink-soft">{{ $listing->description }}</p>
                    </div>
                </a>
            @endforeach
        </section>

        <div>
            {{ $this->souqListings->links() }}
        </div>
    @else
        <section class="rounded-[8px] bg-white px-6 py-12 text-center" style="border: 1px solid var(--border);">
            <p class="text-sm font-light leading-7 text-ink-soft">No businesses found — check back soon, insha'Allah</p>
        </section>
    @endif
</div>
