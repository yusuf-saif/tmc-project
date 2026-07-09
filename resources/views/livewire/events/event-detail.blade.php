<div class="space-y-6">
    @push('backButton')
        <a href="{{ route('events') }}" class="back-btn">&larr; All Events</a>
    @endpush

    <section class="overflow-hidden rounded-[8px] bg-white" style="border:1px solid var(--border);">
        @if ($event->cover_image_path)
            <img src="{{ Storage::disk('r2')->url($event->cover_image_path) }}" alt="{{ $event->title }}" class="max-h-[240px] w-full object-cover">
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
                <p>📅 {{ $event->event_date->hijri('d M Y · H:i') }}</p>
                <p>
                    📍
                    <span class="location-badge location-badge-{{ $event->location_type === 'online' ? 'online' : ($event->location_type === 'in_person' ? 'person' : 'hybrid') }}">
                        {{ match($event->location_type) { 'online' => 'Online', 'in_person' => 'In Person', 'hybrid' => 'Hybrid' } }}
                    </span>
                    @if ($event->location_detail)
                        <span class="ml-2">{{ $event->location_detail }}</span>
                    @endif
                </p>
                <p>👥 {{ $event->active_rsvps_count }} people going</p>
            </div>

            @if ($event->status === 'cancelled')
                <div class="event-cancelled">This event has been cancelled</div>
            @endif

            <div class="text-[0.9rem] font-light leading-8 text-ink-md">
                {!! $event->description !!}
            </div>
        </div>

        @if ($event->cover_image_path)
            <img src="{{ Storage::disk('r2')->url($event->cover_image_path) }}" alt="{{ $event->title }}"
                 class="w-full object-cover">
        @endif

        <div class="space-y-5 p-5">
            @if ($event->external_link)
                <a href="{{ $event->external_link }}" target="_blank" rel="noreferrer"
                   class="btn-teal-outline" style="text-decoration:none;">Join Online →</a>
            @endif

            @if ($event->status !== 'cancelled' && $event->event_date >= now())
                <div class="event-rsvp-box space-y-3">
                    @if (! $isRsvpd)
                        <button type="button" wire:click="rsvp" class="btn-gold-full"
                                wire:loading.attr="disabled">
                            RSVP - I'll be there
                        </button>
                    @else
                        <p class="text-center font-display text-3xl text-teal">You're going, insha'Allah ✓</p>
                        <button type="button" wire:click="cancelRsvp"
                                class="btn-teal-outline" style="width:100%;"
                                wire:loading.attr="disabled">
                            Cancel RSVP
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
