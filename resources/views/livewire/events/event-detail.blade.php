<div class="space-y-6">
    <a href="{{ route('events') }}" class="inline-flex items-center text-[13px] font-medium text-teal">&larr; All Events</a>

    <section class="overflow-hidden rounded-[8px] bg-white" style="border: 1px solid var(--border);">
        @if ($event->cover_image_path)
            <img src="{{ asset('storage/'.$event->cover_image_path) }}" alt="{{ $event->title }}" class="max-h-[240px] w-full object-cover">
        @else
            <div class="flex h-[120px] items-center justify-center bg-teal text-center text-white">
                <div>
                    <p class="font-display text-4xl leading-none">TMC</p>
                    <p class="mt-2 font-arabic text-3xl leading-none">م</p>
                </div>
            </div>
        @endif

        <div class="space-y-5 p-5">
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $event->title }}</h1>

            <div class="space-y-2 text-sm font-light text-ink-soft">
                <p>📅 {{ $event->event_date->format('d M Y · H:i') }}</p>
                <p>
                    📍
                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-medium align-middle" style="background: {{ $event->location_type === 'online' ? '#D6EDEF' : ($event->location_type === 'in_person' ? '#FDF6E3' : '#F3EAF7') }}; color: {{ $event->location_type === 'online' ? '#1A6B72' : ($event->location_type === 'in_person' ? '#C8A84B' : '#3D1A47') }};">
                        {{ match($event->location_type) { 'online' => 'Online', 'in_person' => 'In Person', 'hybrid' => 'Hybrid' } }}
                    </span>
                    @if ($event->location_detail)
                        <span class="ml-2">{{ $event->location_detail }}</span>
                    @endif
                </p>
                <p>👥 {{ $event->active_rsvps_count }} people going</p>
            </div>

            @if ($event->status === 'cancelled')
                <div class="rounded-[8px] px-4 py-3 text-sm font-medium text-red-700" style="background: #FFF5F5; border: 1px solid #C53030;">
                    This event has been cancelled
                </div>
            @endif

            <div class="text-[0.9rem] font-light leading-8 text-ink-md">
                {!! $event->description !!}
            </div>

            @if ($event->external_link)
                <a href="{{ $event->external_link }}" target="_blank" rel="noreferrer" class="inline-flex min-h-11 items-center justify-center px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal transition" style="border: 1px solid #1A6B72; border-radius: 2px;">
                    Join Online →
                </a>
            @endif

            @if ($event->status !== 'cancelled' && $event->event_date >= now())
                <div class="space-y-3 rounded-[8px] px-4 py-4" style="background: #FAF8F3; border: 1px solid var(--border);">
                    @if (! $isRsvpd)
                        <button type="button" wire:click="rsvp" class="inline-flex min-h-11 w-full items-center justify-center bg-gold px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal-dk transition" style="border-radius: 2px;">
                            RSVP - I'll be there
                        </button>
                    @else
                        <p class="text-center font-display text-3xl text-teal">You're going, insha'Allah ✓</p>
                        <button type="button" wire:click="cancelRsvp" class="inline-flex min-h-10 w-full items-center justify-center px-4 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-ink-md transition" style="border: 1px solid var(--border); border-radius: 2px;">
                            Cancel RSVP
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
