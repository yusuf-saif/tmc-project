<div class="space-y-8">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">Our Community</h1>
    </section>

    <section class="space-y-4">
        <div>
            <p class="text-[11px] uppercase tracking-[1.4px] text-gold">Spaces</p>
            <h2 class="mt-2 text-sm font-semibold text-ink-md">Find your circle</h2>
        </div>

        @if ($spaces->count())
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach ($spaces as $space)
                <a href="{{ route('community.spaces.show', $space->slug) }}" class="overflow-hidden rounded-[8px] bg-white no-underline transition hover:-translate-y-[2px] hover:shadow-sm" style="border:1px solid var(--border);">
                    @if ($space->cover_image_path)
                        <img src="{{ Storage::url($space->cover_image_path) }}" alt="{{ $space->name }}" class="aspect-[16/9] w-full object-cover">
                    @else
                        <div class="h-20 w-full bg-gradient-to-r from-teal-dk to-teal"></div>
                    @endif
                    <div class="space-y-2 p-4">
                        @if ($space->is_youth_space)
                            <span class="inline-flex rounded-full bg-gold-pale px-2.5 py-1 text-[11px] font-medium text-gold">Youth Space</span>
                        @endif
                        <h3 class="text-sm font-semibold text-ink">{{ $space->name }}</h3>
                        <p class="line-clamp-2 text-[12px] font-light leading-6 text-ink-soft">{{ $space->short_description }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        @else
        <div class="card empty-state">
            <p class="empty-state-title">No spaces yet</p>
            <p class="empty-state-sub">Community spaces will appear here soon, insha'Allah</p>
        </div>
        @endif
    </section>

    <section class="space-y-4">
        <div>
            <p class="text-[11px] uppercase tracking-[1.4px] text-gold">Get Involved</p>
            <h2 class="mt-2 text-sm font-semibold text-ink-md">Give back to the sisterhood</h2>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-[8px] bg-teal p-5 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m6 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <h3 class="mt-4 text-sm font-semibold">Volunteer with Us</h3>
                <p class="mt-2 text-[12px] font-light leading-6 text-white/80">Contribute your skills and time to the community</p>
                <a href="{{ route('community.support', 'volunteer') }}" class="community-volunteer-btn mt-4">Apply to Volunteer</a>
            </div>

            <div class="rounded-[8px] bg-gold-pale p-5 text-teal">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75 12 3l8.25 3.75M4.5 10.5h15M6.75 10.5v5.25a1.5 1.5 0 0 0 1.5 1.5h7.5a1.5 1.5 0 0 0 1.5-1.5V10.5M9.75 21h4.5"/></svg>
                <h3 class="mt-4 text-sm font-semibold text-teal">Mentorship Programme</h3>
                <p class="mt-2 text-[12px] font-light leading-6 text-ink-soft">Join as a mentor or mentee insha'Allah</p>
                <a href="{{ route('community.support', 'mentorship') }}" class="tmc-button-outline mt-4 max-w-[220px] no-underline">Join the Programme</a>
            </div>

            <div class="rounded-[8px] bg-ivory p-5" style="border:1px solid var(--border);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-gold"><path stroke-linecap="round" stroke-linejoin="round" d="m21 8.25c0-2.485-2.239-4.5-5-4.5-1.885 0-3.526.94-4.388 2.332C10.75 4.69 9.11 3.75 7.224 3.75c-2.76 0-5 2.015-5 4.5 0 7.22 9.388 12 9.388 12S21 15.47 21 8.25Z"/></svg>
                <h3 class="mt-4 text-sm font-semibold text-ink">Support TMC</h3>
                <p class="mt-2 text-[12px] font-light leading-6 text-ink-soft">Help us keep the sisterhood thriving</p>
                <a href="{{ route('community.donate') }}" class="tmc-button-gold mt-4 max-w-[180px] no-underline">Donate</a>
            </div>
        </div>
    </section>
</div>
