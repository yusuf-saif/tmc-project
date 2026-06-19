<nav aria-label="Bottom navigation" class="bottom-nav">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['href'] }}"
           class="bn-tab {{ $tab['active'] ? 'active' : '' }}"
           @if ($tab['active']) aria-current="page" @endif>
            <span class="bn-icon">{!! $tab['icon'] !!}</span>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
