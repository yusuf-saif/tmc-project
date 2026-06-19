<div>

  {{-- Page header --}}
  <div class="page-wrap anim-fade-up" style="margin-bottom:12px;">
    <h1 class="souq-page-title">Halaqahs &amp; Events</h1>
    <p class="souq-page-subtitle">Stay close to the gatherings and sisterhood moments ahead.</p>
  </div>

  {{-- Tab bar --}}
  <div class="tab-bar anim-slide-down">
    @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'my_rsvps' => 'My RSVPs'] as $value => $label)
      <button type="button" wire:click="switchTab('{{ $value }}')"
              class="tab-item {{ $tab === $value ? 'active' : '' }}">
        {{ $label }}
      </button>
    @endforeach
  </div>

  {{-- Event list --}}
  <div class="page-pad" style="padding-top:16px;">

    @if ($this->events->isNotEmpty())

      @foreach ($this->events as $event)
      <div class="event-card anim-fade-up" style="animation-delay:{{ $loop->index * 0.06 }}s;">

        {{-- Cover image or gradient placeholder --}}
        @if ($event->cover_image_path)
          <img src="{{ asset('storage/'.$event->cover_image_path) }}"
               alt="{{ $event->title }}"
               class="event-cover-img">
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

          <div class="event-footer">
            <div class="event-footer-meta">
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
              <span class="event-footer-count">{{ $event->active_rsvps_count }} going</span>
            </div>

            <div class="event-footer-actions">
              <a href="{{ route('events.show', $event->slug) }}" class="event-footer-link">Details</a>

              @if ($tab === 'upcoming')
                @if ($this->isRsvpd($event->id))
                  <span class="btn btn-sm btn-teal-ol" style="cursor:default;opacity:0.8;">Going ✓</span>
                @else
                  <button type="button" wire:click="rsvp({{ $event->id }})" class="btn btn-teal btn-sm">
                    RSVP
                  </button>
                @endif
              @endif

              @if ($tab === 'my_rsvps')
                <button type="button" wire:click="cancelRsvp({{ $event->id }})"
                        wire:confirm="Cancel your RSVP?" class="btn btn-sm btn-teal-ol">
                  Cancel
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>
      @endforeach

    @else
      <div class="card empty-state">
        <p class="empty-state-icon">{{ $this->emptyStateMessage() }}</p>
      </div>
    @endif

  </div>
</div>
