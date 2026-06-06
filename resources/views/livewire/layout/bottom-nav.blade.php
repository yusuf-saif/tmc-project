<nav aria-label="Bottom navigation"
     class="bottom-nav fixed bottom-0 left-0 right-0 z-50 bg-white"
     style="height: 64px; border-top: 1px solid var(--border);">
    <div class="mx-auto flex h-full w-full max-w-md items-center">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}"
               class="flex h-full flex-1 flex-col items-center justify-center gap-[3px] no-underline transition {{ $tab['active'] ? 'text-teal' : 'text-ink-soft' }}"
               style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;"
               @if ($tab['active']) aria-current="page" @endif>
                <span class="flex h-[22px] w-[22px] items-center justify-center">
                    {!! $tab['icon'] !!}
                </span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
