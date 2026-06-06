<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1A6B72">
    <title>{{ $title ?? config('app.name', 'The Muhsinat Club') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body style="background: #F5F3EE; margin: 0; padding: 0; font-family: 'Nunito', sans-serif;">

    {{-- Centred app shell --}}
    <div style="max-width: 480px; margin: 0 auto; min-height: 100vh;
                background: #FAF8F3; position: relative;
                box-shadow: 0 0 60px rgba(0,0,0,0.08);">

        {{-- Top bar --}}
        <div style="padding: 12px 16px; display: flex; align-items: center;
                    justify-content: space-between; background: #FAF8F3;
                    position: sticky; top: 0; z-index: 40;
                    border-bottom: 1px solid #F0EDE8;">
            <a href="{{ route('home') }}" style="display: inline-flex; align-items: center;">
                <img src="{{ asset('images/img1.png') }}" alt="TMC"
                     style="width: 32px; height: 32px; object-fit: contain;">
            </a>
            <button type="button" aria-label="Notifications"
                    style="background: none; border: none; cursor: pointer; color: #6B6760; padding: 4px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.5"
                     style="width: 24px; height: 24px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.311 6.022c1.766.68 3.559 1.09 5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
            </button>
        </div>

        {{-- Page content --}}
        <main style="padding-bottom: 80px;">
            {{ $slot }}
        </main>

        {{-- Bottom nav --}}
        <livewire:layout.bottom-nav />

    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-end="opacity-0 translate-y-2"
             style="position: fixed; bottom: 90px; left: 50%;
                    transform: translateX(-50%);
                    background: #1A6B72; color: white;
                    padding: 10px 20px; border-radius: 20px;
                    font-size: 13px; font-family: 'Nunito', sans-serif;
                    z-index: 100; white-space: nowrap;
                    box-shadow: 0 4px 20px rgba(26,107,114,0.3)">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div x-data="{ show: true }" x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-end="opacity-0 translate-y-2"
             style="position: fixed; bottom: 90px; left: 50%;
                    transform: translateX(-50%);
                    background: #C53030; color: white;
                    padding: 10px 20px; border-radius: 20px;
                    font-size: 13px; font-family: 'Nunito', sans-serif;
                    z-index: 100; white-space: nowrap;
                    box-shadow: 0 4px 20px rgba(197,48,48,0.3)">
            {{ session('error') }}
        </div>
    @endif

    @livewireScripts
</body>
</html>
