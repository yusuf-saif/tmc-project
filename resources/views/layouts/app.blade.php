<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#1A6B72">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <title>{{ $title ?? 'The Muhsinat Club' }}</title>
  <link rel="icon" href="{{ asset('images/img1.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/img1.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @endif
  @livewireStyles
</head>
<body>

<div class="app-shell">

  {{-- Top bar --}}
  <header class="top-bar">
    <a href="{{ route('home') }}" style="display:inline-flex;align-items:center;">
      <img src="{{ asset('images/img1.png') }}"
           alt="TMC"
           style="width:32px;height:32px;object-fit:contain;">
    </a>
    <span class="text-[11px] font-medium text-teal-dk/70" style="font-feature-settings:'tnum';">
      {{ now()->hijri('j M Y') }}
    </span>
    <a href="{{ route('profile', ['tab' => 'notifications']) }}" class="topbar-btn" aria-label="Notifications">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31
                 A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75
                 a8.967 8.967 0 0 1-2.311 6.022c1.766.68 3.559 1.09
                 5.454 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0
                 m5.714 0a3 3 0 1 1-5.714 0"/>
      </svg>
      @if(auth()->check() && auth()->user()->unreadNotifications()->count() > 0)
        <span class="notif-badge"></span>
      @endif
    </a>
  </header>

  {{-- Main content --}}
  <main>
    {{ $slot }}
  </main>

  {{-- Bottom nav --}}
  <livewire:layout.bottom-nav />

</div>

{{-- Toast: success --}}
@if(session('success'))
  <div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 3000)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="toast toast-success">
    ✓ {{ session('success') }}
  </div>
@endif

{{-- Toast: error --}}
@if(session('error'))
  <div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4000)"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-end="opacity-0 translate-y-2"
    class="toast toast-error">
    {{ session('error') }}
  </div>
@endif

<livewire:announcement-popup />

@livewireScripts
</body>
</html>
