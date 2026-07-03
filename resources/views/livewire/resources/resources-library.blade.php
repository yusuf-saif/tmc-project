<div class="space-y-6">
    <section class="space-y-2">
        <h1 class="font-display text-[1.8rem] leading-none text-teal">Resources</h1>
        <p class="text-sm font-light leading-7 text-ink-soft">Browse reflections, guides, du\'as, and recorded reminders to return to throughout the week.</p>
    </section>

    @php $categories = $this->categories @endphp
    <section class="-mx-4 overflow-x-auto px-4">
        <div class="resource-tabs">
            <button type="button" wire:click="setCategory('all')"
                    class="resource-tab {{ $category === 'all' ? 'resource-tab-active' : 'resource-tab-inactive' }}">
                All
            </button>
            @foreach ($categories as $cat)
                <button type="button" wire:click="setCategory('{{ $cat->slug }}')"
                        class="resource-tab {{ $category === $cat->slug ? 'resource-tab-active' : 'resource-tab-inactive' }}">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>
    </section>

    <div class="flex gap-3">
        <div class="flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search resources..." class="input">
        </div>
        <select wire:model.live="sort" class="input" style="max-width:130px;">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
            <option value="title_asc">A → Z</option>
            <option value="title_desc">Z → A</option>
        </select>
    </div>

    @if ($this->resources->count())
        <section class="grid grid-cols-2 gap-4 md:grid-cols-3">
            @foreach ($this->resources as $resource)
                @php
                    $badge = $resource->category ? [
                        'bg' => $resource->category->bg_color ?? 'var(--teal-lt)',
                        'text' => $resource->category->text_color ?? 'var(--teal)',
                        'label' => $resource->category->name,
                        'initial' => $resource->category->initial ?? substr($resource->category->name, 0, 1),
                    ] : ['bg' => 'var(--teal-lt)', 'text' => 'var(--teal)', 'label' => 'Resource', 'initial' => 'R'];
                    $icon = match ($resource->type) {
                        'article' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v12A2.25 2.25 0 0 1 16.5 20.25h-9A2.25 2.25 0 0 1 5.25 18V6A2.25 2.25 0 0 1 7.5 3.75Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h4.5"/></svg>',
                        'dua' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75c-1.5-1.125-3.75-1.125-6 0v10.5c2.25-1.125 4.5-1.125 6 0m0-10.5c1.5-1.125 3.75-1.125 6 0v10.5c-2.25-1.125-4.5-1.125-6 0"/></svg>',
                        'pdf' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v3.375c0 .621-.504 1.125-1.125 1.125H5.625A1.125 1.125 0 0 1 4.5 17.625V6.375c0-.621.504-1.125 1.125-1.125h6.75L19.5 12.375Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 5.25v6.75h6.75"/></svg>',
                        'audio' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9.75v4.5m6-7.5v10.5M6 12a6 6 0 0 1 6-6m0 12a6 6 0 0 0 6-6"/></svg>',
                        'video_link' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 5.653 11.54 6.346a.75.75 0 0 1 0 1.313L5.25 19.658A.75.75 0 0 1 4.125 19V6.311a.75.75 0 0 1 1.125-.658Z"/></svg>',
                        'guide' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-[18px] w-[18px]"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20.25h6m-7.5-3h9A2.25 2.25 0 0 0 18.75 15V6A2.25 2.25 0 0 0 16.5 3.75h-9A2.25 2.25 0 0 0 5.25 6v9A2.25 2.25 0 0 0 7.5 17.25Z"/></svg>',
                    };
                @endphp
                <a href="{{ route('resources.show', $resource->slug) }}" class="overflow-hidden rounded-[8px] bg-white no-underline" style="border:1px solid var(--border);">
                    @if ($resource->thumbnail_path)
                        <img src="{{ Storage::url($resource->thumbnail_path) }}" alt="{{ $resource->title }}" class="aspect-[16/9] w-full object-cover">
                    @else
                        <div class="flex aspect-[16/9] w-full items-center justify-center bg-teal text-white">
                            <span class="font-display text-5xl leading-none">{{ $badge['initial'] }}</span>
                        </div>
                    @endif
                    <div class="space-y-3 p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium" style="background:{{ $badge['bg'] }};color:{{ $badge['text'] }};">
                                {{ $badge['label'] }}
                            </span>
                            <span class="text-ink-soft">{!! $icon !!}</span>
                        </div>
                        <div>
                            <h2 class="text-sm font-medium leading-6 text-ink">{{ $resource->title }}</h2>
                            <p class="mt-1 line-clamp-2 text-[12px] font-light leading-6 text-ink-soft">{{ $resource->description }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </section>

        <div>
            {{ $this->resources->links() }}
        </div>
    @else
        <section class="card empty-state">
            <p class="text-sm font-light leading-7 text-ink-soft">No resources found — check back soon, insha'Allah</p>
        </section>
    @endif
</div>
