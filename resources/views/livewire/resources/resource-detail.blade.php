<div class="space-y-6">
    @push('backButton')
        <a href="{{ route('resources') }}" class="back-btn">&larr; Resources</a>
    @endpush

    <section class="overflow-hidden rounded-[8px] bg-white p-5" style="border:1px solid var(--border);">
        @if (in_array($resource->type, ['article', 'guide'], true))
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $resource->title }}</h1>
            @if ($this->bodyContainsHtml())
                <div class="prose mt-5 max-w-none prose-p:font-light prose-p:leading-8 prose-p:text-ink-md prose-headings:font-display prose-headings:text-teal-dk">{!! $resource->body !!}</div>
            @else
                <div class="mt-5 text-base font-light leading-8 text-ink-md">{!! nl2br(e($resource->body ?? '')) !!}</div>
            @endif
        @elseif ($resource->type === 'dua')
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $resource->title }}</h1>
            <div class="mt-5 text-right font-arabic text-[1.4rem] leading-[2] text-ink" dir="rtl">{!! nl2br(e($resource->body ?? '')) !!}</div>
            <p class="mt-4 text-sm font-light italic leading-7 text-ink-soft">{{ $resource->description }}</p>

            @if (! $isSaved)
                <button type="button" wire:click="saveToDuaList"
                        class="btn-teal-outline mt-6"
                        wire:loading.attr="disabled">
                    ✦ Save to My Du'a List
                </button>
            @else
                <button type="button" wire:click="removeFromDuaList"
                        class="mt-6 inline-flex min-h-11 items-center justify-center px-5 text-[12.5px] font-semibold uppercase tracking-[1.2px] text-teal-dk transition"
                        style="background:var(--teal-lt);border:1px solid var(--teal);border-radius:var(--radius-sm);"
                        wire:loading.attr="disabled">
                    ✦ Saved to Du'a List ✓
                </button>
            @endif
        @elseif ($resource->type === 'pdf')
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $resource->title }}</h1>
            <p class="mt-3 text-sm font-light leading-7 text-ink-soft">{{ $resource->description }}</p>
            @if ($resource->file_path)
                <a href="{{ Storage::url($resource->file_path) }}" target="_blank"
                   class="btn-gold-full mt-5" style="max-width:200px;">
                    Download PDF
                </a>
                <iframe src="{{ Storage::url($resource->file_path) }}" class="pdf-preview"></iframe>
            @endif
        @elseif ($resource->type === 'audio')
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $resource->title }}</h1>
            <p class="mt-3 text-sm font-light leading-7 text-ink-soft">{{ $resource->description }}</p>
            @if ($resource->file_path)
                <audio controls class="mt-3 w-full">
                    <source src="{{ Storage::url($resource->file_path) }}">
                </audio>
            @endif
        @elseif ($resource->type === 'video_link')
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $resource->title }}</h1>
            <p class="mt-3 text-sm font-light leading-7 text-ink-soft">{{ $resource->description }}</p>
            @if ($resource->external_url)
                <a href="{{ $resource->external_url }}" target="_blank" rel="noreferrer"
                   class="btn-teal-outline mt-5" style="text-decoration:none;">
                    Watch →
                </a>
            @endif
        @endif
    </section>
</div>
