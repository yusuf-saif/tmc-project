<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline | The Muhsinat Club</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --teal: #1A6B72;
            --teal-dk: #0D3F44;
            --gold: #C8A84B;
            --ivory: #FAF8F3;
            --ink: #1C1A17;
            --ink-soft: #6B6760;
            --display: 'Dancing Script', cursive;
            --body: 'Nunito', sans-serif;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(13, 63, 68, 0.06), rgba(200, 168, 75, 0.12)), var(--ivory);
            color: var(--ink);
            font-family: var(--body);
        }

        .card {
            width: min(100%, 30rem);
            padding: 2.5rem 2rem;
            text-align: center;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(200, 168, 75, 0.3);
            border-radius: 20px;
            box-shadow: 0 24px 60px rgba(26, 107, 114, 0.12);
            backdrop-filter: blur(10px);
        }

        .logo {
            width: 3.5rem;
            height: 3.5rem;
            object-fit: contain;
            margin-bottom: 1rem;
        }

        h1 {
            margin: 0 0 0.75rem;
            color: var(--teal-dk);
            font-family: var(--display);
            font-size: clamp(2.4rem, 8vw, 3.2rem);
            font-weight: 500;
            line-height: 1.05;
        }

        p {
            margin: 0;
            color: var(--ink-soft);
            font-size: 0.95rem;
            line-height: 1.8;
        }

        .rule {
            width: 4rem;
            height: 1px;
            margin: 1.25rem auto;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }
    </style>
</head>
<body>
    <main class="card">
        <img src="{{ asset('images/img1.png') }}" alt="The Muhsinat Club" class="logo">
        <h1>You're offline</h1>
        <div class="rule"></div>
        <p>The Muhsinat Club will be back as soon as your connection returns. Your space is still here waiting for you.</p>
    </main>
</body>
</html>
