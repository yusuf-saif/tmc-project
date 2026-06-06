<div class="space-y-6">
    <a href="{{ route('community') }}" class="inline-flex items-center text-[13px] font-medium text-teal">&larr; Community</a>

    <section class="overflow-hidden rounded-[8px] bg-white" style="border: 1px solid var(--border);">
        @if ($space->cover_image_path)
            <img src="{{ Storage::url($space->cover_image_path) }}" alt="{{ $space->name }}" class="max-h-[200px] w-full object-cover">
        @else
            <div class="h-[100px] w-full bg-teal"></div>
        @endif

        <div class="space-y-6 p-5">
            <div>
                <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $space->name }}</h1>
                @if ($space->is_youth_space)
                    <span class="mt-3 inline-flex rounded-full bg-gold-pale px-2.5 py-1 text-[11px] font-medium text-gold">Youth Space</span>
                @endif
                <p class="mt-4 text-[0.9rem] font-light leading-8 text-ink-md">{{ $space->description }}</p>
            </div>

            @if ($space->guidelines)
                <div class="rounded-[6px] bg-ivory p-4" style="border-left: 3px solid var(--gold);">
                    <h2 class="text-sm font-semibold text-ink">Community Guidelines</h2>
                    <p class="mt-2 text-[0.875rem] font-light leading-7 text-ink-soft">{{ $space->guidelines }}</p>
                </div>
            @endif

            @if ($events->isNotEmpty())
                <div class="space-y-3">
                    <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">Upcoming Halaqahs</p>
                    @foreach ($events as $event)
                        <a href="{{ route('events.show', $event->slug) }}" class="block rounded-[8px] bg-white p-4 no-underline" style="border: 1px solid var(--border);">
                            <p class="text-sm font-semibold text-ink">{{ $event->title }}</p>
                            <p class="mt-1 text-[12px] font-light text-ink-soft">{{ $event->event_date->format('d M Y, g:ia') }}</p>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($resources->isNotEmpty())
                <div class="space-y-3">
                    <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">Resources</p>
                    @foreach ($resources as $resource)
                        <a href="{{ route('resources.show', $resource->slug) }}" class="flex items-center justify-between rounded-[8px] bg-white p-4 no-underline" style="border: 1px solid var(--border);">
                            <p class="text-sm font-semibold text-ink">{{ $resource->title }}</p>
                            <span class="inline-flex rounded-full bg-teal-lt px-2.5 py-1 text-[11px] font-medium text-teal">{{ str_replace('_', ' ', $resource->category) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($space->external_link)
                <a href="{{ $space->external_link }}" target="_blank" rel="noreferrer" class="inline-flex min-h-11 w-full items-center justify-center bg-teal px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-white no-underline" style="border-radius: 2px;">Join Group →</a>
            @endif
        </div>
    </section>
</div>
