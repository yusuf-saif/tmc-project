<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | The Muhsinat Club</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="tmc-auth-body">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl items-center justify-center px-6 py-12">
        <section class="w-full rounded bg-white p-10 text-center shadow-[0_4px_24px_rgba(0,0,0,0.06)]">
            <img src="{{ asset('images/img2.png') }}" alt="The Muhsinat Club" class="mx-auto mb-8 w-48 max-w-full object-contain">
            <h1 class="font-display text-5xl leading-none text-teal-dk">Your member home is ready for Phase 2</h1>
            <p class="mx-auto mt-4 max-w-xl text-sm font-light leading-7 text-ink-soft">Phase 1 now lands authenticated, verified, onboarded members in a branded member route. The full dashboard follows in the next build phase.</p>

            <form method="POST" action="{{ route('logout') }}" class="mx-auto mt-8 max-w-xs">
                @csrf
                <button type="submit" class="tmc-button-outline">Sign Out</button>
            </form>
        </section>
    </main>
</body>
</html>
