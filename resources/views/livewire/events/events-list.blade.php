<div>

  {{-- Page header --}}
  <div class="page-wrap anim-fade-up" style="margin-bottom:12px;">
    <h1 style="font-family:var(--font-display);font-size:1.8rem;
               color:var(--teal);line-height:1;margin-bottom:6px;">
      Halaqahs &amp; Events
    </h1>
    <p style="font-size:0.875rem;font-weight:300;color:var(--ink-soft);line-height:1.6;">
      Stay close to the gatherings and sisterhood moments ahead.
    </p>
  </div>

  {{-- Tab bar --}}
  <div class="tab-bar anim-slide-down">
    @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'my_rsvps' => 'My RSVPs'] as $value => $label)
      <button
        type="button"
        wire:click="switchTab('{{ $value }}')"
        class="tab-item {{ $tab === $value ? 'active' : '' }}"
      >{{ $label }}</button>
    @endforeach
  </div>

  {{-- Event list --}}
  <div class="page-pad" style="padding-top:16px;">

    @if ($this->events->isNotEmpty())

      @foreach ($this->events as $event)
      <div class="event-card anim-fade-up"
           style="animation-delay:{{ $loop->index * 0.06 }}s;">

        {{-- Cover image or gradient placeholder --}}
        @if ($event->cover_image_path)
          <img src="{{ asset('storage/'.$event->cover_image_path) }}"
               alt="{{ $event->title }}"
               style="width:100%;height:140px;object-fit:cover;display:block;">
        @else
          <div class="event-card__placeholder">
            <span>TMC</span>
          </div>
        @endif

        <div class="event-card__body">
          <p class="event-card__title">{{ $event->title }}</p>

          <div class="event-card__meta">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
              <rect x="3" y="4" width="18" height="18" rx="2"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
            </svg>
            <span>{{ $event->event_date->format('D d M Y · g:ia') }}</span>
          </div>

          <div class="event-card__footer">
            <div style="display:flex;align-items:center;gap:6px;">
              @php
                $badgeClass = match($event->location_type) {
                  'in_person' => 'badge-gold',
                  'hybrid'    => 'badge-plum',
                  default     => 'badge-teal',
                };
                $locationLabel = match($event->location_type) {
                  'online'    => 'Online',
                  'in_person' => 'In Person',
                  'hybrid'    => 'Hybrid',
                  default     => ucfirst($event->location_type),
                };
              @endphp
              <span class="badge {{ $badgeClass }}">{{ $locationLabel }}</span>
              <span style="font-size:11px;color:var(--ink-soft);">
                {{ $event->active_rsvps_count }} going
              </span>
            </div>

            <div style="display:flex;gap:8px;align-items:center;">
              <a href="{{ route('events.show', $event->slug) }}"
                 style="font-size:12px;font-weight:500;color:var(--teal);
                        text-decoration:none;">
                Details
              </a>

              @if ($tab === 'upcoming')
                @if ($this->isRsvpd($event->id))
                  <span class="btn btn-sm btn-teal-ol"
                        style="cursor:default;opacity:0.8;">Going ✓</span>
                @else
                  <button type="button"
                          wire:click="rsvp({{ $event->id }})"
                          class="btn btn-teal btn-sm">
                    RSVP
                  </button>
                @endif
              @endif

              @if ($tab === 'my_rsvps')
                <button type="button"
                        wire:click="cancelRsvp({{ $event->id }})"
                        wire:confirm="Cancel your RSVP?"
                        class="btn btn-sm btn-teal-ol">
                  Cancel
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>
      @endforeach

    @else
      <div class="card anim-scale-in"
           style="padding:48px 24px;text-align:center;">
        <p style="font-family:var(--font-display);font-size:1.2rem;
                  color:var(--teal);margin-bottom:8px;">
          {{ $this->emptyStateMessage() }}
        </p>
      </div>
    @endif

  </div>
</div>
