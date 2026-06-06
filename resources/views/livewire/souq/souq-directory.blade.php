<div class="space-y-6">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">The Souq</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Muslim women-owned businesses, curated for the sisterhood</p>
    </section>

    <div style="display:flex; overflow-x:auto; gap:8px; padding:0 0 8px;
                border-bottom: 1px solid var(--border);
                -webkit-overflow-scrolling: touch; scrollbar-width: none;">
        @foreach (['all' => 'All', 'fashion' => 'Fashion', 'food_catering' => 'Food & Catering', 'health_beauty' => 'Health & Beauty', 'education' => 'Education', 'services' => 'Services', 'creative' => 'Creative', 'other' => 'Other'] as $value => $label)
            <button type="button" wire:click="setCategory('{{ $value }}')"
                    style="padding: 8px 14px; white-space: nowrap; background: none; cursor: pointer;
                           font-family:'Nunito',sans-serif; font-size:13px; font-weight:600;
                           text-transform:uppercase; letter-spacing:1.2px;
                           border: none; border-bottom: 2px solid {{ $category === $value ? '#1A6B72' : 'transparent' }};
                           color: {{ $category === $value ? '#1A6B72' : '#6B6760' }};
                           border-radius: 0; flex-shrink: 0;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search businesses..." class="w-full bg-white px-4 py-3 text-[14px] text-ink outline-none" style="border: 1px solid var(--border); border-radius: 6px;">
    </div>

    <div style="display:flex; justify-content:flex-end;">
        <a href="{{ route('souq.apply') }}"
           style="padding: 8px 18px; background: #C8A84B; color: #0D3F44;
                  border-radius: 6px; font-family:'Nunito',sans-serif;
                  font-size: 12px; font-weight: 600; letter-spacing: 0.5px;
                  text-transform: uppercase; text-decoration: none;
                  display: block; width: fit-content;">
            List My Business
        </a>
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
                    <div>
                        @if ($listing->logo_path)
                            <img src="{{ Storage::url($listing->logo_path) }}" alt="{{ $listing->business_name }}"
                                 style="width:56px; height:56px; border-radius:50%;
                                        object-fit:cover; border:1px solid #E2E8F0;
                                        margin: 0 auto 10px; display:block;">
                        @else
                            <div style="width:56px; height:56px; border-radius:50%;
                                        background: #1A6B72; display:flex;
                                        align-items:center; justify-content:center;
                                        margin: 0 auto 10px;">
                                <span style="font-family:'Dancing Script',cursive;
                                             font-size:1.4rem; color:white; line-height:1">
                                    {{ strtoupper(mb_substr($listing->business_name, 0, 1)) }}
                                </span>
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
