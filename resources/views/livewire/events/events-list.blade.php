<div class="space-y-6 px-4">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">Halaqahs &amp; Events</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Stay close to the gatherings, reminders, and sisterhood moments ahead.</p>
    </section>

    <section class="flex overflow-x-auto border-b" style="border-color: var(--border); gap: 0;">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'my_rsvps' => 'My RSVPs'] as $value => $label)
            <button
                type="button"
                wire:click="switchTab('{{ $value }}')"
                class="border-b-2 transition"
                style="padding: 10px 16px; font-family:'Nunito',sans-serif; font-size:13px;
                       font-weight:500; text-transform:uppercase; letter-spacing:0.5px;
                       white-space:nowrap; background:none; cursor:pointer;
                       border-color: {{ $tab === $value ? '#1A6B72' : 'transparent' }};
                       color: {{ $tab === $value ? '#1A6B72' : '#6B6760' }};
                       border-radius: 0;"
            >
                {{ $label }}
            </button>
        @endforeach
    </section>

    @if ($this->events->isNotEmpty())
        <section class="space-y-3">
            @foreach ($this->events as $event)
                <article class="overflow-hidden rounded-[12px] bg-white transition hover:shadow-[0_4px_12px_rgba(0,0,0,0.06)]" style="border: 1px solid var(--border);">
                    @if ($event->cover_image_path)
                        <img src="{{ asset('storage/'.$event->cover_image_path) }}" alt="{{ $event->title }}"
                             style="width:100%; height:140px; object-fit:cover; border-radius:10px 10px 0 0; display:block;">
                    @else
                        <div style="width:100%; height:100px;
                                    background: linear-gradient(135deg, #1A6B72, #2A8A93);
                                    border-radius:10px 10px 0 0;
                                    display: flex; align-items: center; justify-content: center;">
                            <span style="font-family:'Dancing Script',cursive;
                                         font-size:1.2rem; color:rgba(255,255,255,0.7)">TMC</span>
                        </div>
                    @endif

                    <div class="space-y-4 p-4">
                        <div class="space-y-3">
                            <h2 class="text-[0.95rem] font-semibold text-ink">{{ $event->title }}</h2>
                            <div class="flex items-center gap-2 text-[12px] font-light text-ink-soft">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[14px] w-[14px]"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span>{{ $event->event_date->format('d M Y, g:ia') }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-medium" style="background: {{ $event->location_type === 'online' ? '#D6EDEF' : ($event->location_type === 'in_person' ? '#FDF6E3' : '#F3EAF7') }}; color: {{ $event->location_type === 'online' ? '#1A6B72' : ($event->location_type === 'in_person' ? '#C8A84B' : '#3D1A47') }};">
                                    {{ match($event->location_type) { 'online' => 'Online', 'in_person' => 'In Person', 'hybrid' => 'Hybrid' } }}
                                </span>
                                <span class="text-[11px] text-ink-soft">{{ $event->active_rsvps_count }} going</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <a href="{{ route('events.show', $event->slug) }}" class="text-[12px] font-medium text-teal no-underline">
                                View Details
                            </a>

                            @if ($tab === 'upcoming')
                                @if ($this->isRsvpd($event->id))
                                    <button type="button" disabled class="inline-flex min-h-10 items-center justify-center px-4 text-[12px] font-semibold uppercase text-teal" style="background: #D6EDEF; border: 1px solid #1A6B72; border-radius: 6px;">
                                        Going ✓
                                    </button>
                                @else
                                    <button type="button" wire:click="rsvp({{ $event->id }})" class="inline-flex min-h-10 items-center justify-center bg-teal px-4 text-[12px] font-semibold uppercase text-white transition" style="border-radius: 6px;">
                                        RSVP
                                    </button>
                                @endif
                            @endif

                            @if ($tab === 'my_rsvps')
                                <button type="button" wire:click="cancelRsvp({{ $event->id }})" class="inline-flex min-h-10 items-center justify-center px-4 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-ink-md transition" style="border: 1px solid var(--border); border-radius: 2px;">
                                    Cancel RSVP
                                </button>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    @else
        <section class="rounded-[8px] bg-white px-6 py-12 text-center" style="border: 1px solid var(--border);">
            <p class="text-sm font-light leading-7 text-ink-soft">{{ $this->emptyStateMessage() }}</p>
        </section>
    @endif
</div>
