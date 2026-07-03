<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Muhsinat Club</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="{{ asset('images/img1-small.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/img1-small.png') }}">
<link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="loading">

{{-- Preloader --}}
<div class="preloader" id="preloader">
  <div class="preloader-card">
    <img src="{{ asset('images/img1-small.png') }}" alt="TMC" class="preloader-mark">
    <div class="preloader-arabic ar">المحسنات</div>
    <div class="preloader-name">The Muhsinat Club</div>
    <div class="preloader-tag">Ajr Hunting for the Home in Jannah</div>
    <div class="preloader-line"></div>
  </div>
</div>

<div class="progress" id="progress"></div>
<div class="cursor-ring" id="cursor-ring"></div>
<div class="cursor-dot" id="cursor-dot"></div>

{{-- Navigation --}}
<nav class="site-nav" id="site-nav">
  <a class="brand" href="#top">
    <img src="{{ asset('images/img1-nav.png') }}" alt="TMC" class="nav-logo-img">
    <span class="brand-name">The Muhsinat Club</span>
  </a>
  <div class="nav-right">
    <ul class="nav-links">
      <li><a href="#features">Platform</a></li>
      <li><a href="#community">Sisterhood</a></li>
      <li><a href="#about">About</a></li>
    </ul>
    <div class="nav-actions">
      <a class="btn btn-outline btn-sm nav-cta" href="{{ url('/admin/login') }}">Admin</a>
      <a class="btn btn-gold btn-sm nav-cta" href="{{ route('membership.signup') }}">Register</a>
      <a class="btn btn-gold btn-sm nav-cta" href="{{ url('/login') }}">Login</a>
    </div>
  </div>
</nav>

<main id="top">

  {{-- Hero --}}
  <section class="hero">
    <div class="hero-left">
      <div class="hero-copy">
        <h1 class="hero-title">
          <span>A home for</span>
          <span>faith &amp; sisterhood</span>
          <span>that lasts.</span>
        </h1>
        <p class="hero-tagline">Ajr Hunting for the Home in Jannah</p>
        <p class="hero-body">A refined community platform for Muslim women seeking sacred routines, sincere companionship, and beautiful tools for worship, reflection, learning, and service.</p>
        <div class="hero-actions">
          <a class="btn btn-gold" href="#join">Join the Club</a>
          <a class="btn btn-ghost" href="#features">Explore the platform</a>
        </div>
      </div>
      <div class="hero-proof">
        <div class="avatars">
          <span class="avatar">A</span>
          <span class="avatar">F</span>
          <span class="avatar">y</span>
          <span class="avatar">K</span>
        </div>
        <p class="proof-text">Sisters across the community are already inside</p>
      </div>
    </div>
    <div class="hero-right">
      <div class="calligraphy-panel">
        <div class="rule"></div>
        <div class="hero-arabic-word ar">المحسنات</div>
        <div class="rule"></div>
        <p class="translation">Striving for Excellence.</p>
        <svg class="star" viewBox="0 0 100 100"><path fill="currentColor" d="M50 0 61 32 93 7 68 39 100 50 68 61 93 93 61 68 50 100 39 68 7 93 32 61 0 50 32 39 7 7 39 32Z"/></svg>
        <div class="right-stats">
          <div class="right-stat"><strong>500+</strong><span>Sisters</span></div>
          <div class="right-stat"><strong>7</strong><span>Events monthly</span></div>
          <div class="right-stat"><strong>∞</strong><span>Ajr potential</span></div>
        </div>
      </div>
    </div>
  </section>

  {{-- Ornamental divider --}}
  <div class="ornament">
    <div class="ornament-line"></div>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l2.5 7.2H22l-6 4.5 2.3 7.3L12 16.7 5.7 21 8 13.7 2 9.2h7.5z"/></svg>
    <div class="ornament-line"></div>
  </div>

  {{-- Marquee Band --}}
  <section class="band">
    <div class="band-track" id="band-track"></div>
  </section>

  {{-- Stats --}}
  <section class="stats-bar">
    <div class="stat">
      <div class="stat-number" data-count="350" data-suffix="+">0</div>
      <div class="stat-label">Sisters &amp; growing</div>
    </div>
    <div class="stat">
      <div class="stat-number" data-count="12">0</div>
      <div class="stat-label">Halaqahs this year</div>
    </div>
    <div class="stat">
      <div class="stat-number" data-count="5000" data-suffix="+">0</div>
      <div class="stat-label">Total Coins Awarded</div>
    </div>
  </section>

  {{-- Features --}}
  <section class="section features" id="features">
    <div class="section-inner">
      <div class="reveal">
        <span class="eyebrow">The Platform</span>
        <h2 class="section-heading">Everything a <em>Muhsinah</em> needs, in one <em>sacred space.</em></h2>
      </div>
      <div class="features-grid">
        <article class="feature-card wide reveal-left" style="transition-delay:0.05s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div>
          <h3 class="feature-title">Halaqahs &amp; Events</h3>
          <p class="feature-body">Browse upcoming circles, reserve your seat, and keep your week anchored by gatherings designed for knowledge, remembrance, and meaningful connection.</p>
          <span class="feature-tag">RSVP enabled</span>
        </article>
        <article class="feature-card reveal-right" style="transition-delay:0.15s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M12 2l2.5 7.2H22l-6 4.5 2.3 7.3L12 16.7 5.7 21 8 13.7 2 9.2h7.5z"/></svg></div>
          <h3 class="feature-title">Jannah Coins</h3>
          <p class="feature-body">Receive thoughtful rewards for showing up, inviting sisters, attending gatherings, and building habits that beautify your everyday worship.</p>
          <span class="feature-tag">Earn &amp; redeem</span>
        </article>
        <article class="feature-card reveal-right" style="transition-delay:0.25s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 19.5 5 15l11-11a2.1 2.1 0 0 1 3 3L8 18z"/><path d="M13.5 5.5l5 5"/><path d="M4 20h16"/></svg></div>
          <h3 class="feature-title">Private Journal</h3>
          <p class="feature-body">A quiet space for reflections, gratitude, goals, and du'a. Private by design, gentle in tone, and always ready when your heart needs room.</p>
          <span class="feature-tag">Fully private</span>
        </article>
        <article class="feature-card reveal-left" style="transition-delay:0.1s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M6 7h12l-1 14H7z"/><path d="M9 7a3 3 0 0 1 6 0"/><path d="M6 7 4 3h16l-2 4"/></svg></div>
          <h3 class="feature-title">The Souq</h3>
          <p class="feature-body">Discover Muslim women-owned brands, support values-aligned commerce, and list your own work inside a trusted member marketplace.</p>
          <span class="feature-tag">Shop with sisters</span>
        </article>
        <article class="feature-card wide reveal-right" style="transition-delay:0.2s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h5"/><path d="M7 14h10"/><circle cx="17" cy="10" r="1.5"/></svg></div>
          <h3 class="feature-title">Legacy Card</h3>
          <p class="feature-body">Carry a digital membership card that honours your place in the sisterhood, your progress, and the small consistent acts that shape a lasting legacy.</p>
          <span class="feature-tag">Exclusive to members</span>
        </article>
        <article class="feature-card reveal-left" style="transition-delay:0.3s">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
          <h3 class="feature-title">Resources Library</h3>
          <p class="feature-body">Access curated du'a guides, reflections, study notes, and recordings for the seasons when you need knowledge close at hand.</p>
          <span class="feature-tag">Curated content</span>
        </article>
      </div>
    </div>
  </section>

  {{-- Arabic Divider --}}
  <section class="arabic-divider">
    <div class="divider-line"></div>
    <span class="divider-arabic ar">ٱلْمُحْسِنَاتُ</span>
    <div class="divider-line bottom"></div>
    <p class="divider-caption">The Muhsinat Club</p>
  </section>

  {{-- How It Works --}}
  <section class="section how" id="how">
    <div class="section-inner">
      <div class="reveal">
        <span class="eyebrow">How it works</span>
        <h2 class="section-heading light">From intention to <em>sisterhood</em> in minutes.</h2>
      </div>
      <div class="steps">
        <article class="step">
          <div class="step-number">01</div>
          <div class="step-line"></div>
          <h3 class="step-title">Create your account</h3>
          <p class="step-body">Begin with your name, interests, and spiritual goals so the platform can feel personal, calm, and genuinely useful from the first visit.</p>
        </article>
        <article class="step">
          <div class="step-number">02</div>
          <div class="step-line"></div>
          <h3 class="step-title">Choose your rhythm</h3>
          <p class="step-body">Find halaqahs, journal prompts, resources, and reminders that support the season you are in without pressure or noise.</p>
        </article>
        <article class="step">
          <div class="step-number">03</div>
          <div class="step-line"></div>
          <h3 class="step-title">Grow with sisters</h3>
          <p class="step-body">Show up, earn Jannah Coins, support the Souq, and build friendships rooted in sincerity, service, and shared hope for Jannah.</p>
        </article>
      </div>
    </div>
  </section>

  {{-- Testimonials --}}
  <section class="section testimonials" id="community">
    <div class="section-inner">
      <div class="reveal">
        <span class="eyebrow">The Sisterhood</span>
        <h2 class="section-heading">Words from the <em>club.</em></h2>
      </div>
      <div class="testimonial-grid">
        <article class="testimonial reveal-scale" style="transition-delay:0.05s">
          <div class="quote-mark">"</div>
          <p class="quote">The journal feels like a quiet room for my thoughts. I come in for a few minutes and leave with my heart softer and my intentions clearer.</p>
          <div class="author"><span class="author-initials">A</span><div><p class="author-name">Amina K.</p><p class="author-location">United Kingdom</p></div></div>
        </article>
        <article class="testimonial reveal-scale" style="transition-delay:0.15s">
          <div class="quote-mark">"</div>
          <p class="quote">TMC makes faith feel beautifully practical. The halaqahs, reminders, and sisterly warmth helped me rebuild consistency without feeling alone.</p>
          <div class="author"><span class="author-initials">F</span><div><p class="author-name">Fatimah O.</p><p class="author-location">Nigeria</p></div></div>
        </article>
        <article class="testimonial reveal-scale" style="transition-delay:0.25s">
          <div class="quote-mark">"</div>
          <p class="quote">The Souq introduced me to women-led brands I now genuinely love. It feels good to support sisters while staying connected to the community.</p>
          <div class="author"><span class="author-initials">Z</span><div><p class="author-name">Zainab M.</p><p class="author-location">Canada</p></div></div>
        </article>
      </div>
    </div>
  </section>

  {{-- CTA --}}
  <section class="section cta" id="join">
    <div class="cta-inner reveal">
      <p class="cta-arabic ar">وَاللَّهُ يُحِبُّ الْمُحْسِنِينَ</p>
      <p class="cta-translation">"And Allah loves those who do good." — Qur'an 3:134</p>
      <p class="cta-eyebrow">Ready, sister?</p>
      <h2 class="cta-heading">Your seat in the <em>sisterhood</em> is waiting.</h2>
      <p class="cta-body">Step into a warm, refined space for worship, learning, reflection, rewards, and sincere companionship built for Muslim women.</p>
      <div class="cta-actions">
        <a class="btn btn-gold" href="{{ url('/login') }}">Sign In</a>
        <a class="btn btn-rose" href="{{ route('membership.signup') }}">Create Your Account</a>
      </div>
    </div>
  </section>

</main>

{{-- Footer --}}
<footer class="footer" id="about">
  <div class="footer-inner">
    <div class="footer-top">
      <div>
        <div class="footer-brand-row">
          <img src="{{ asset('images/img1-nav.png') }}" alt="TMC" style="height:44px;object-fit:contain;">
          <p class="footer-brand">The Muhsinat Club</p>
        </div>
        <p class="footer-tagline">Ajr Hunting for the Home in Jannah</p>
        <p class="footer-desc">A premium faith-based community platform for Muslim women pursuing goodness, sisterhood, knowledge, and lasting reward.</p>
      </div>
      <div>
        <h3 class="footer-heading">Platform</h3>
        <ul class="footer-links">
          <li><a href="#features">Halaqahs &amp; Events</a></li>
          <li><a href="#features">Private Journal</a></li>
          <li><a href="#features">Jannah Coins</a></li>
          <li><a href="#features">Member Souq</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-heading">Community</h3>
        <ul class="footer-links">
          <li><a href="#community">Sister Stories</a></li>
          <li><a href="#how">How It Works</a></li>
          <li><a href="#features">Legacy Card</a></li>
          <li><a href="#join">Join TMC</a></li>
        </ul>
      </div>
      <div>
        <h3 class="footer-heading">About</h3>
        <ul class="footer-links">
          <li><a href="#about">Our Intention</a></li>
          <li><a href="#about">Safety</a></li>
          <li><a href="#about">Contact</a></li>
          <li><a href="#about">Support</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <span id="year"></span> The Muhsinat Club. All rights reserved.</p>
      <div>
        <a href="#about">Privacy</a>
        <a href="#about">Terms</a>
      </div>
    </div>
  </div>
</footer>

<script defer src="{{ asset('js/landing.js') }}"></script>
</body>
</html>
