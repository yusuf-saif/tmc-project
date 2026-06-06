<div>

  {{-- Page header --}}
  <div class="page-wrap anim-fade-up" style="margin-bottom:4px;">
    <h1 style="font-family:var(--font-display);font-size:1.8rem;
               color:var(--teal);line-height:1;margin-bottom:6px;">
      The Souq
    </h1>
    <p style="font-size:0.875rem;font-weight:300;color:var(--ink-soft);line-height:1.6;">
      Muslim women-owned businesses, curated for the sisterhood
    </p>
  </div>

  {{-- Category pill tabs --}}
  <div class="anim-slide-down"
       style="display:flex;overflow-x:auto;gap:6px;
              padding:10px 16px 12px;scrollbar-width:none;
              -webkit-overflow-scrolling:touch;
              border-bottom:1px solid var(--border);">
    @foreach(['all' => 'All', 'fashion' => 'Fashion', 'food_catering' => 'Food & Catering',
              'health_beauty' => 'Health & Beauty', 'education' => 'Education',
              'services' => 'Services', 'creative' => 'Creative', 'other' => 'Other']
             as $value => $label)
      <button type="button"
              wire:click="setCategory('{{ $value }}')"
              style="padding:6px 14px;border-radius:20px;
                     font-family:var(--font-body);font-size:11px;
                     font-weight:600;text-transform:uppercase;
                     letter-spacing:0.5px;white-space:nowrap;
                     cursor:pointer;transition:all 0.2s;flex-shrink:0;
                     background:{{ $category === $value ? 'var(--teal)' : 'var(--white)' }};
                     color:{{ $category === $value ? 'white' : 'var(--ink-soft)' }};
                     border:1.5px solid {{ $category === $value ? 'var(--teal)' : 'var(--border)' }};">
        {{ $label }}
      </button>
    @endforeach
  </div>

  {{-- Search + List My Business --}}
  <div class="page-pad anim-fade-up delay-1"
       style="padding-top:12px;padding-bottom:4px;">
    <input type="text"
           wire:model.live.debounce.300ms="search"
           placeholder="Search businesses..."
           class="input"
           style="margin-bottom:10px;">
    <div style="display:flex;justify-content:flex-end;margin-bottom:4px;">
      <a href="{{ route('souq.apply') }}"
         style="padding:7px 16px;background:var(--gold);color:var(--teal-dk);
                border-radius:6px;font-family:var(--font-body);
                font-size:11px;font-weight:600;letter-spacing:0.5px;
                text-transform:uppercase;text-decoration:none;
                display:inline-block;">
        List My Business
      </a>
    </div>
  </div>

  {{-- Listings grid --}}
  <div class="page-pad anim-fade-up delay-2"
       style="padding-top:8px;">
    @if ($this->souqListings->count())
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
        @foreach ($this->souqListings as $listing)
          @php
            $badge = match ($listing->category) {
              'fashion'        => ['bg' => 'var(--plum-lt)',  'text' => 'var(--plum)'],
              'food_catering'  => ['bg' => 'var(--gold-pale)', 'text' => '#8A6A1A'],
              'health_beauty'  => ['bg' => 'var(--teal-lt)',  'text' => 'var(--teal)'],
              'education'      => ['bg' => 'var(--mint-lt)',  'text' => '#1A6B5A'],
              'services'       => ['bg' => '#F0EDE8',         'text' => 'var(--ink-soft)'],
              'creative'       => ['bg' => 'var(--plum-lt)',  'text' => 'var(--plum)'],
              default          => ['bg' => '#F0EDE8',         'text' => 'var(--ink-soft)'],
            };
          @endphp
          <a href="{{ route('souq.show', $listing->slug) }}" class="souq-card">
            @if ($listing->logo_path)
              <img src="{{ Storage::url($listing->logo_path) }}"
                   alt="{{ $listing->business_name }}"
                   class="souq-logo">
            @else
              <div class="souq-logo-fallback">
                <span style="font-family:var(--font-display);font-size:1.4rem;
                             color:white;line-height:1;">
                  {{ strtoupper(mb_substr($listing->business_name, 0, 1)) }}
                </span>
              </div>
            @endif

            <p style="font-size:0.875rem;font-weight:600;color:var(--ink);
                      line-height:1.3;margin-bottom:6px;">
              {{ $listing->business_name }}
            </p>
            <span style="display:inline-flex;padding:3px 8px;border-radius:20px;
                         font-size:10px;font-weight:600;text-transform:uppercase;
                         letter-spacing:0.4px;margin-bottom:8px;
                         background:{{ $badge['bg'] }};color:{{ $badge['text'] }};">
              {{ $listing->categoryLabel() }}
            </span>
            <p style="font-size:12px;font-weight:300;color:var(--ink-soft);
                      line-height:1.5;display:-webkit-box;
                      -webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
              {{ $listing->description }}
            </p>
          </a>
        @endforeach
      </div>

      <div style="margin-top:16px;">
        {{ $this->souqListings->links() }}
      </div>

    @else
      <div class="card anim-scale-in"
           style="padding:48px 24px;text-align:center;">
        <p style="font-family:var(--font-display);font-size:1.1rem;
                  color:var(--teal);margin-bottom:4px;">
          No businesses found
        </p>
        <p style="font-size:0.875rem;font-weight:300;color:var(--ink-soft);">
          Check back soon, insha'Allah
        </p>
      </div>
    @endif
  </div>

</div>
