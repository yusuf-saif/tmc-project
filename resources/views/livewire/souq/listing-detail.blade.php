@php($instagramHandle = $this->instagramHandle())
@php($websiteLabel = $this->websiteLabel())

<div class="space-y-6">
    <a href="{{ route('souq') }}" class="inline-flex items-center text-[13px] font-medium text-teal">&larr; The Souq</a>

    <section class="rounded-[8px] bg-white p-6" style="border: 1px solid var(--border);">
        <div class="flex flex-col items-center text-center">
            @if ($listing->logo_path)
                <img src="{{ Storage::url($listing->logo_path) }}" alt="{{ $listing->business_name }}" class="h-20 w-20 rounded-full border object-contain" style="border-color: var(--border);">
            @else
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-teal text-white">
                    <span class="font-display text-[2rem] leading-none">{{ strtoupper(mb_substr($listing->business_name, 0, 1)) }}</span>
                </div>
            @endif

            <h1 class="mt-4 font-display text-[2rem] leading-none text-teal-dk">{{ $listing->business_name }}</h1>
            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-[11px] font-medium" style="background: var(--teal-lt); color: var(--teal);">
                {{ $listing->categoryLabel() }}
            </span>
        </div>

        <div class="mt-6 text-[0.9rem] font-light leading-8 text-ink-md">
            {{ $listing->description }}
        </div>

        <div class="mt-8 space-y-4">
            <div class="flex items-start gap-3 text-sm text-ink-md">
                <span class="mt-0.5 text-teal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9m19.5 0A2.25 2.25 0 0 0 19.5 5.25h-15A2.25 2.25 0 0 0 2.25 7.5m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 9.659A2.25 2.25 0 0 1 2.25 7.743V7.5"/></svg>
                </span>
                <a href="mailto:{{ $listing->contact_email }}" class="text-teal">{{ $listing->contact_email }}</a>
            </div>

            @if ($listing->phone)
                <div class="flex items-start gap-3 text-sm text-ink-md">
                    <span class="mt-0.5 text-teal">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 7.87 6.38 14.25 14.25 14.25h2.25A2.25 2.25 0 0 0 21 18.75v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293a.75.75 0 0 1-.99.206 11.205 11.205 0 0 1-5.69-5.69.75.75 0 0 1 .206-.99l1.293-.97c.335-.251.498-.674.417-1.173L7.713 3.85A1.125 1.125 0 0 0 6.622 3H5.25A2.25 2.25 0 0 0 3 5.25v1.5"/></svg>
                    </span>
                    <a href="tel:{{ $listing->phone }}" class="text-teal">{{ $listing->phone }}</a>
                </div>
            @endif

            @if ($listing->website)
                <div class="flex items-start gap-3 text-sm text-ink-md">
                    <span class="mt-0.5 text-teal">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c1.933 0 3.5-4.03 3.5-9s-1.567-9-3.5-9-3.5 4.03-3.5 9 1.567 9 3.5 9Zm0 0c4.97 0 9-1.567 9-3.5S16.97 14 12 14s-9 1.567-9 3.5S7.03 21 12 21Zm0-18c4.97 0 9 1.567 9 3.5S16.97 10 12 10 3 8.433 3 6.5 7.03 3 12 3Z"/></svg>
                    </span>
                    <a href="{{ $listing->website }}" target="_blank" rel="noreferrer" class="text-teal">{{ $websiteLabel }}</a>
                </div>
            @endif

            @if ($instagramHandle)
                <div class="flex items-start gap-3 text-sm text-ink-md">
                    <span class="mt-0.5 text-teal">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 8.25A4.5 4.5 0 0 1 8.25 3.75h7.5a4.5 4.5 0 0 1 4.5 4.5v7.5a4.5 4.5 0 0 1-4.5 4.5h-7.5a4.5 4.5 0 0 1-4.5-4.5v-7.5ZM9.75 12a2.25 2.25 0 1 0 4.5 0 2.25 2.25 0 0 0-4.5 0Zm5.625-4.875h.008v.008h-.008v-.008Z"/></svg>
                    </span>
                    <a href="https://instagram.com/{{ $instagramHandle }}" target="_blank" rel="noreferrer" class="text-teal">&#64;{{ $instagramHandle }}</a>
                </div>
            @endif
        </div>
    </section>
</div>
