<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'The Muhsinat Club') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="bg-ivory text-ink antialiased">

    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur-sm">
        <div class="mx-auto flex h-14 w-full max-w-md items-center justify-between px-4">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                <img src="{{ asset('images/img1.png') }}" alt="TMC" class="h-7 w-auto object-contain">
            </a>
            <button type="button" aria-label="Notifications" class="text-ink-soft hover:text-ink-md">
                <!-- placeholder bell icon -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.311 6.022c1.766.68 3.559 1.09 5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
            </button>
        </div>
    </header>

    <main class="mx-auto w-full max-w-md px-4 pb-[calc(64px+env(safe-area-inset-bottom))] pt-4">
        {{ $slot }}
    </main>

    <livewire:layout.bottom-nav />

    @livewireScripts
</body>
</html>
