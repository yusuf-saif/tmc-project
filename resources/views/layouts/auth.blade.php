<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Auth' }} | The Muhsinat Club</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @livewireStyles
</head>
<body class="tmc-auth-body">
    <main class="tmc-auth-shell">
        <section class="tmc-auth-card">
            <div class="tmc-auth-content">
                <a href="{{ route('landing') }}" class="block">
                    <img src="{{ asset('images/img1.png') }}" alt="The Muhsinat Club" class="tmc-auth-logo">
                </a>
                <p class="tmc-auth-brand">The Muhsinat Club</p>

                @yield('content')
            </div>
        </section>
    </main>
    @livewireScripts
</body>
</html>
