<div class="space-y-6">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">Halaqahs &amp; Events</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Stay close to the gatherings, reminders, and sisterhood moments ahead.</p>
    </section>

    <section class="flex items-center gap-5 border-b" style="border-color: var(--border);">
        @foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'my_rsvps' => 'My RSVPs'] as $value => $label)
            <button
                type="button"
                wire:click="switchTab('{{ $value }}')"
                class="border-b-2 pb-3 text-[13px] font-semibold uppercase tracking-[1.2px] transition"
                style="border-color: {{ $tab === $value ? '#1A6B72' : 'transparent' }}; color: {{ $tab === $value ? '#1A6B72' : '#6B6760' }}; border-radius: 0;"
            >
                {{ $label }}
            </button>
        @endforeach
    </section>

    @if ($this->events->isNotEmpty())
        <section class="space-y-4">
            @foreach ($this->events as $event)
                <article class="overflow-hidden rounded-[8px] bg-white" style="border: 1px solid var(--border);">
                    @if ($event->cover_image_path)
                        <img src="{{ asset('storage/'.$event->cover_image_path) }}" alt="{{ $event->title }}" class="aspect-[16/9] w-full object-cover">
                    @else
                        <div class="flex aspect-[16/9] w-full items-center justify-center bg-teal text-center text-white">
                            <div>
                                <p class="font-display text-5xl leading-none">TMC</p>
                                <p class="mt-2 font-arabic text-3xl leading-none">م</p>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-4 p-4">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-medium" style="background: {{ $event->location_type === 'online' ? '#D6EDEF' : ($event->location_type === 'in_person' ? '#FDF6E3' : '#F3EAF7') }}; color: {{ $event->location_type === 'online' ? '#1A6B72' : ($event->location_type === 'in_person' ? '#C8A84B' : '#3D1A47') }};">
                                    {{ match($event->location_type) { 'online' => 'Online', 'in_person' => 'In Person', 'hybrid' => 'Hybrid' } }}
                                </span>
                                <span class="text-[11px] text-ink-soft">{{ $event->active_rsvps_count }} going</span>
                            </div>

                            <h2 class="text-base font-medium text-ink">{{ $event->title }}</h2>
                            <p class="text-sm font-light text-ink-soft">{{ $event->event_date->format('d M Y') }} · {{ $event->event_date->format('H:i') }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('events.show', $event->slug) }}" class="inline-flex min-h-10 items-center justify-center px-4 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal transition" style="border: 1px solid #1A6B72; border-radius: 2px;">
                                View Details
                            </a>

                            @if ($tab === 'upcoming')
                                @if ($this->isRsvpd($event->id))
                                    <button type="button" disabled class="inline-flex min-h-10 items-center justify-center px-4 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal-dk" style="background: #D6EDEF; border-radius: 2px;">
                                        You're going ✓
                                    </button>
                                @else
                                    <button type="button" wire:click="rsvp({{ $event->id }})" class="inline-flex min-h-10 items-center justify-center bg-teal px-4 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-white transition" style="border-radius: 2px;">
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
