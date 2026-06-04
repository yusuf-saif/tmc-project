<nav aria-label="Bottom navigation"
     class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white"
     style="height: 64px; padding-bottom: env(safe-area-inset-bottom);">
    <ul class="mx-auto grid h-full max-w-md grid-cols-7 items-center gap-0">
        @foreach($tabs as $tab)
            <li class="h-full">
                <a href="{{ $tab['href'] }}"
                   class="flex h-full flex-col items-center justify-center gap-1 transition"
                   @if($tab['active']) aria-current="page" @endif>
                    <span class="{{ $tab['active'] ? 'text-teal' : 'text-ink-soft' }}">
                        {!! $tab['icon'] !!}
                    </span>
                    <span class="text-[10px] uppercase tracking-[1px] {{ $tab['active'] ? 'text-teal' : 'text-ink-soft' }}">
                        {{ $tab['label'] }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
