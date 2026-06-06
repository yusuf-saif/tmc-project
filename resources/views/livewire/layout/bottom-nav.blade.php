<nav aria-label="Bottom navigation"
     class="bottom-nav z-50 bg-white"
     style="position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            width: 100%; max-width: 480px; height: 64px;
            border-top: 1px solid var(--border);">
    <div class="flex h-full w-full items-center">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}"
               class="flex h-full flex-1 flex-col items-center justify-center py-2 no-underline transition {{ $tab['active'] ? 'text-teal' : 'text-ink-soft' }}"
               style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.4px;"
               @if ($tab['active']) aria-current="page" @endif>
                @if ($tab['active'])
                    <div style="width:4px;height:4px;border-radius:50%;background:var(--teal);margin-bottom:2px"></div>
                @else
                    <div style="width:4px;height:4px;margin-bottom:2px"></div>
                @endif
                <span class="flex items-center justify-center" style="width:22px;height:22px;">
                    {!! $tab['icon'] !!}
                </span>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>
