<div>

  {{-- Page header --}}
  <div class="page-wrap anim-fade-up souq-page-header">
    <h1 class="souq-page-title">The Souq</h1>
    <p class="souq-page-subtitle">Muslim women-owned businesses, curated for the sisterhood</p>
  </div>

  {{-- Category pill tabs --}}
  <div class="souq-pills anim-slide-down">
    @foreach(['all' => 'All', 'fashion' => 'Fashion', 'food_catering' => 'Food & Catering',
              'health_beauty' => 'Health & Beauty', 'education' => 'Education',
              'services' => 'Services', 'creative' => 'Creative', 'other' => 'Other']
             as $value => $label)
      <button type="button"
              wire:click="setCategory('{{ $value }}')"
              class="souq-pill {{ $category === $value ? 'souq-pill-active' : 'souq-pill-inactive' }}">
        {{ $label }}
      </button>
    @endforeach
  </div>

  {{-- Search + List My Business --}}
  <div class="page-pad anim-fade-up delay-1 souq-search-wrap">
    <div class="flex gap-3">
      <input type="text"
             wire:model.live.debounce.300ms="search"
             placeholder="Search businesses..."
             class="input souq-search-input" style="flex:1;">
      <select wire:model.live="sort" class="input" style="max-width:130px;">
        <option value="newest">Newest</option>
        <option value="oldest">Oldest</option>
        <option value="title_asc">A → Z</option>
        <option value="title_desc">Z → A</option>
      </select>
    </div>
    <div class="souq-apply-wrap">
      <a href="{{ route('souq.apply') }}" class="souq-apply-btn">List My Business</a>
    </div>
  </div>

  {{-- Listings grid --}}
  <div class="page-pad anim-fade-up delay-2 souq-grid-wrap">
    @if ($this->souqListings->count())
      <div class="souq-grid">
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

            <p class="souq-card-name">{{ $listing->business_name }}</p>
            <span class="badge" style="background:{{ $badge['bg'] }};color:{{ $badge['text'] }};margin-bottom:8px;">
              {{ $listing->categoryLabel() }}
            </span>
            <p class="souq-card-desc">{{ $listing->description }}</p>
          </a>
        @endforeach
      </div>

      <div class="souq-pagination">
        {{ $this->souqListings->links() }}
      </div>

    @else
      <div class="card anim-scale-in souq-empty">
        <p class="souq-empty-title">No businesses found</p>
        <p class="souq-empty-sub">Check back soon, insha'Allah</p>
      </div>
    @endif
  </div>

</div>
