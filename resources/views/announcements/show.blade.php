@component('layouts.app', ['title' => $announcement->title])
    <div class="space-y-6">
        <a href="{{ route('home') }}" class="inline-flex items-center text-[13px] font-medium text-teal">&larr; Home</a>
        <section class="rounded-[8px] bg-white p-6" style="border: 1px solid var(--border);">
            <h1 class="font-display text-[2rem] leading-none text-teal-dk">{{ $announcement->title }}</h1>
            <p class="mt-3 text-[12px] font-light text-ink-soft">{{ optional($announcement->published_at)->format('d M Y') }}</p>

            <div class="mt-6 text-[0.9rem] font-light leading-8 text-ink-md">
                @if (
                    Illuminate\Support\Str::contains($announcement->body, '<')
                )
                    <div class="prose max-w-none">{!! $announcement->body !!}</div>
                @else
                    <p style="white-space: pre-wrap">{{ $announcement->body }}</p>
                @endif
            </div>
        </section>
    </div>
@endcomponent
