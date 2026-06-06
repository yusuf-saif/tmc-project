<div class="space-y-6 pt-4" style="padding: 0 16px;">
    <!-- A — Greeting card -->
    <section class="text-white" style="background: var(--teal); background-image: url('/images/img4.png'); background-size: 400px; background-repeat: repeat; border-radius: 16px; padding: 1.4rem 1.2rem; margin-bottom: 1rem; position: relative; overflow: hidden;">
        <div style="position:absolute;inset:0;background:rgba(13,63,68,0.6);border-radius:16px"></div>
        <div style="position:relative;z-index:1">
            <h1 style="font-family:'Dancing Script',cursive;font-size:1.5rem;line-height:1.3;color:#fff;">{{ $greeting }}</h1>
            <p style="font-family:'Dancing Script',cursive;font-style:italic;font-size:0.95rem;color:rgba(255,255,255,0.6);margin-top:0.35rem;">{{ $dailyPhrase }}</p>
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
    <a href="{{ route('wallet') }}" class="no-underline" style="background: rgba(200,168,75,0.08); border: 1px solid rgba(200,168,75,0.25); border-radius: 12px; padding: 1rem 1.2rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px]" style="background: var(--gold); color: var(--teal-dk);">✦</span>
            <div>
                <p class="font-display text-[1.5rem] leading-none text-gold">{{ number_format($balance) }}</p>
                <p class="mt-1 text-[11px] uppercase tracking-[1px] text-ink-soft">Jannah Coins</p>
            </div>
        </div>
        <span class="text-[12px] font-medium text-teal">View Wallet →</span>
    </a>

    <!-- D — Upcoming events preview -->
    <section class="space-y-3">
        <p class="mb-2 text-[11px] uppercase tracking-[1.2px] text-ink-soft">UPCOMING HALAQAHS</p>
        @if(count($events))
            <div class="space-y-3">
                @foreach($events as $event)
                    <div class="flex items-center justify-between rounded-[12px] bg-white" style="border: 1px solid #E2E8F0; padding: 12px; margin-bottom: 8px;">
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
    <div style="padding: 0 0 1.5rem;">
      <p style="font-size:11px; font-weight:600; letter-spacing:2px;
                 text-transform:uppercase; color:#6B6760;
                 margin-bottom:0.75rem; font-family:'Nunito',sans-serif">
        Quick Actions
      </p>
      <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:10px;">

        <a href="{{ route('journal') }}"
           style="display:flex; flex-direction:column; align-items:center;
                  justify-content:center; background:white; border-radius:12px;
                  border:1px solid #E2E8F0; padding:12px 8px; gap:6px;
                  text-decoration:none; min-height:72px; max-height:80px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="#1A6B72" stroke-width="1.5" stroke-linecap="round"
               stroke-linejoin="round" style="flex-shrink:0; width:22px; height:22px">
            <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4z"/>
          </svg>
          <span style="font-size:10px; font-weight:500; text-transform:uppercase;
                       letter-spacing:0.5px; color:#6B6760; font-family:'Nunito',sans-serif;
                       line-height:1; white-space:nowrap">Journal</span>
        </a>

        <a href="{{ route('journal') }}"
           style="display:flex; flex-direction:column; align-items:center;
                  justify-content:center; background:white; border-radius:12px;
                  border:1px solid #E2E8F0; padding:12px 8px; gap:6px;
                  text-decoration:none; min-height:72px; max-height:80px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="#1A6B72" stroke-width="1.5" stroke-linecap="round"
               stroke-linejoin="round" style="flex-shrink:0; width:22px; height:22px">
            <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477
                     3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477
                     4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0
                     3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5
                     18c-1.746 0-3.332.477-4.5 1.253"/>
          </svg>
          <span style="font-size:10px; font-weight:500; text-transform:uppercase;
                       letter-spacing:0.5px; color:#6B6760; font-family:'Nunito',sans-serif;
                       line-height:1; white-space:nowrap">Du'a</span>
        </a>

        <a href="{{ route('events') }}"
           style="display:flex; flex-direction:column; align-items:center;
                  justify-content:center; background:white; border-radius:12px;
                  border:1px solid #E2E8F0; padding:12px 8px; gap:6px;
                  text-decoration:none; min-height:72px; max-height:80px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="#1A6B72" stroke-width="1.5" stroke-linecap="round"
               stroke-linejoin="round" style="flex-shrink:0; width:22px; height:22px">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          <span style="font-size:10px; font-weight:500; text-transform:uppercase;
                       letter-spacing:0.5px; color:#6B6760; font-family:'Nunito',sans-serif;
                       line-height:1; white-space:nowrap">Events</span>
        </a>

        <a href="{{ route('souq') }}"
           style="display:flex; flex-direction:column; align-items:center;
                  justify-content:center; background:white; border-radius:12px;
                  border:1px solid #E2E8F0; padding:12px 8px; gap:6px;
                  text-decoration:none; min-height:72px; max-height:80px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="#1A6B72" stroke-width="1.5" stroke-linecap="round"
               stroke-linejoin="round" style="flex-shrink:0; width:22px; height:22px">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 01-8 0"/>
          </svg>
          <span style="font-size:10px; font-weight:500; text-transform:uppercase;
                       letter-spacing:0.5px; color:#6B6760; font-family:'Nunito',sans-serif;
                       line-height:1; white-space:nowrap">Souq</span>
        </a>

      </div>
    </div>

    <!-- F — Support TMC banner -->
    <a href="/community" class="block rounded-[8px] bg-teal-lt px-4 py-4 text-teal no-underline">
        <span class="font-medium">Support our mission →</span>
    </a>
</div>
