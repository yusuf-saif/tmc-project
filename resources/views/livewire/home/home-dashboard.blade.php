<div class="space-y-6">
    <!-- A — Greeting card -->
    <section class="relative overflow-hidden rounded-[12px] bg-teal p-5 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-10" style="background-image: url('/images/img4.png'); background-repeat: repeat; background-size: 360px auto; opacity: 0.08;"></div>
        <div class="relative z-10">
            <h1 class="font-display text-[1.8rem] leading-snug">{{ $greeting }}</h1>
            <p class="mt-1 font-display italic text-white/70">{{ $dailyPhrase }}</p>
        </div>
    </section>

    <!-- B — Announcement banner (only if exists) -->
    @if($announcement)
        <a href="{{ route('announcements.show', $announcement->slug) }}" class="block rounded-[8px] bg-gold px-4 py-3 text-teal-dk no-underline">
            <p class="font-medium">{{ $announcement->title }}</p>
            <p class="mt-1 text-sm text-teal-dk/85">{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body ?? ''), 100) }}</p>
        </a>
    @endif

    <!-- C — Coins snapshot card -->
    <a href="/wallet" class="block rounded-[10px] border px-4 py-4 no-underline" style="background: rgba(200,168,75,0.08); border-color: rgba(200,168,75,0.22);">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full" style="background: #C8A84B; color: #1C1A17;">✦</span>
            <div class="flex items-baseline gap-3">
                <span class="font-display text-[1.4rem]" style="color: #E8CB7A;">{{ number_format($balance) }}</span>
                <span class="text-[11px] uppercase tracking-[1px] text-ink-soft">Jannah Coins</span>
            </div>
        </div>
    </a>

    <!-- D — Upcoming events preview -->
    <section class="space-y-3">
        <p class="text-[11px] uppercase tracking-[1.2px] text-ink-soft">UPCOMING HALAQAHS</p>
        @if(count($events))
            <div class="space-y-3">
                @foreach($events as $event)
                    <div class="flex items-center justify-between rounded-[8px] border border-slate-200 bg-white p-4">
                        <div>
                            <p class="font-semibold text-ink">{{ $event->title }}</p>
                            <p class="mt-1 text-sm text-ink-soft">
                                {{ \Carbon\Carbon::parse($event->event_date)->format('D d M') }} · {{ \Carbon\Carbon::parse($event->event_date)->format('g:ia') }}
                            </p>
                            @php($badge = match($event->location_type) {
                                'online' => ['bg' => 'bg-teal-lt', 'text' => 'text-teal', 'label' => 'Online'],
                                'in_person' => ['bg' => 'bg-gold-pale', 'text' => 'text-gold', 'label' => 'In Person'],
                                'hybrid' => ['bg' => 'bg-ivory', 'text' => 'text-ink', 'label' => 'Hybrid'],
                                default => ['bg' => 'bg-ivory', 'text' => 'text-ink', 'label' => '']
                            })
                            <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-xs {{ $badge['bg'] }} {{ $badge['text'] }}">{{ $badge['label'] }}</span>
                        </div>
                        <a href="/events/{{ $event->slug }}" class="inline-flex items-center justify-center rounded-[2px] bg-teal px-3 py-2 text-[11px] font-semibold uppercase tracking-[1px] text-white">RSVP</a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="py-6 text-center font-display italic text-teal-md">No upcoming halaqahs — check back soon</p>
        @endif
    </section>

    <!-- E — Quick actions row -->
    <section class="grid grid-cols-4 gap-3">
        <a href="/journal" class="flex flex-col items-center gap-2 rounded-[8px] border border-slate-200 bg-white p-4 text-center no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 3.75V18a2.25 2.25 0 0 1-2.25 2.25H7.5A2.25 2.25 0 0 1 5.25 18V5.25A1.5 1.5 0 0 1 6.75 3.75h9.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25h4.5M9 12h3"/></svg>
            <span class="text-[10px] uppercase tracking-[1px] text-ink-soft">Journal</span>
        </a>
        <a href="/resources?category=dua_book" class="flex flex-col items-center gap-2 rounded-[8px] border border-slate-200 bg-white p-4 text-center no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c-1.5-1.125-3.75-1.125-6 0v10.5c2.25-1.125 4.5-1.125 6 0m0-10.5c1.5-1.125 3.75-1.125 6 0v10.5c-2.25-1.125-4.5-1.125-6 0"/></svg>
            <span class="text-[10px] uppercase tracking-[1px] text-ink-soft">Du'a Book</span>
        </a>
        <a href="/events" class="flex flex-col items-center gap-2 rounded-[8px] border border-slate-200 bg-white p-4 text-center no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25A2.25 2.25 0 0 1 18.75 21H5.25A2.25 2.25 0 0 1 3 18.75Zm0-9h18"/></svg>
            <span class="text-[10px] uppercase tracking-[1px] text-ink-soft">Events</span>
        </a>
        <a href="/souq" class="flex flex-col items-center gap-2 rounded-[8px] border border-slate-200 bg-white p-4 text-center no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-teal"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5 4.5 3.75h15L21 7.5M3 7.5h18M4.5 7.5V18a1.5 1.5 0 0 0 1.5 1.5h12a1.5 1.5 0 0 0 1.5-1.5V7.5M9 21V12h6v9"/></svg>
            <span class="text-[10px] uppercase tracking-[1px] text-ink-soft">Souq</span>
        </a>
    </section>

    <!-- F — Support TMC banner -->
    <a href="/community" class="block rounded-[8px] bg-teal-lt px-4 py-4 text-teal no-underline">
        <span class="font-medium">Support our mission →</span>
    </a>
</div>
