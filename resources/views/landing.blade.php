<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Muhsinat Club</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
<link rel="icon" type="image/png" href="{{ asset('images/img1.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/img1.png') }}">
<style>
:root {
  --teal: #1A6B72;
  --teal-dk: #0D3F44;
  --teal-hero: #0A3035;
  --teal-md: #2A8A93;
  --teal-lt: #E4F2F3;
  --gold: #C8A84B;
  --gold-lt: #E8CB7A;
  --gold-pale: #FDF6E3;
  --rose: #D4A0A0;
  --rose-lt: #F5E6E6;
  --rose-pale: #FDF2F2;
  --ivory: #FAF8F3;
  --ink: #1C1A17;
  --ink-mid: #3D3A35;
  --ink-soft: #6B6760;
  --white: #FFFFFF;
  --display: 'Dancing Script', cursive;
  --body: 'Nunito', sans-serif;
  --arabic: 'Amiri', serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; scroll-padding-top: 80px; }
body { background: var(--ivory); color: var(--ink); font-family: var(--body); overflow-x: hidden; }
body.loading { overflow: hidden; }
a, button { color: inherit; text-decoration: none; }
button { cursor: pointer; border: none; background: none; font-family: inherit; }
.ar { direction: rtl; font-family: var(--arabic); unicode-bidi: embed; }

/* ── CURSOR ──────────────────────────────────────── */
.cursor-dot, .cursor-ring { display: none; pointer-events: none; position: fixed; transform: translate(-50%, -50%); z-index: 9999; }
.cursor-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--gold); }
.cursor-ring { width: 32px; height: 32px; border-radius: 50%; border: 0.5px solid rgba(200,168,75,0.7); transition: width 0.22s ease, height 0.22s ease; }
@media (hover: hover) and (pointer: fine) { body, a, button { cursor: none; } .cursor-dot, .cursor-ring { display: block; } }

/* ── PROGRESS BAR ────────────────────────────────── */
.progress { position: fixed; top: 0; left: 0; height: 2px; width: 0; background: linear-gradient(90deg, var(--gold), var(--gold-lt), var(--rose)); z-index: 300; }

/* ── PRELOADER ───────────────────────────────────── */
.preloader { position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center; justify-content: center; background: var(--teal-hero); overflow: hidden; transition: opacity 0.8s ease, visibility 0.8s ease; }
.preloader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
.preloader::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 300px auto; opacity: 0.08; animation: drift 40s linear infinite; }
.preloader-card { position: relative; z-index: 1; text-align: center; padding: 3rem clamp(2rem, 5vw, 4.5rem); border: 1px solid rgba(200,168,75,0.25); display: flex; flex-direction: column; align-items: center; gap: 0.8rem; max-width: calc(100vw - 2rem); }
.preloader-card::before, .preloader-card::after { content: ''; position: absolute; left: 50%; transform: translateX(-50%); width: 68px; height: 1px; background: var(--gold); opacity: 0.7; }
.preloader-card::before { top: 1.25rem; }
.preloader-card::after { bottom: 1.25rem; }
.preloader-mark { width: 52px; height: 52px; object-fit: contain; margin-bottom: 0.2rem; }
.preloader-arabic { font-size: clamp(3rem, 9vw, 5.8rem); color: rgba(200,168,75,0.9); font-weight: 700; line-height: 1; }
.preloader-name { font-family: var(--display); font-size: 1.55rem; color: white; line-height: 1; }
.preloader-tag { font-family: var(--display); font-size: 1rem; color: rgba(255,255,255,0.5); font-style: italic; }
.preloader-line { width: min(220px, 54vw); height: 1px; background: rgba(255,255,255,0.12); margin-top: 0.8rem; position: relative; overflow: hidden; }
.preloader-line::after { content: ''; position: absolute; top: 0; left: 0; height: 100%; width: 70%; background: linear-gradient(90deg, transparent, var(--gold), transparent); animation: preloaderSlide 1.45s ease-in-out infinite; }
@keyframes preloaderSlide { to { transform: translateX(170%); } }

/* ── BUTTONS ─────────────────────────────────────── */
.btn { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 28px; border-radius: 4px; font-family: var(--body); font-size: 12px; font-weight: 600; letter-spacing: 0.9px; text-transform: uppercase; position: relative; overflow: hidden; transition: all 0.28s ease; }
.btn::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent); transform: translateX(-105%); transition: transform 0.55s ease; }
.btn:hover::after { transform: translateX(105%); }
.btn:hover { transform: translateY(-2px); }
.btn-gold { background: var(--gold); border: 1px solid var(--gold); color: var(--teal-hero); }
.btn-gold:hover { background: var(--gold-lt); box-shadow: 0 18px 42px rgba(200,168,75,0.25); }
.btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,0.45); color: rgba(255,255,255,0.85); }
.btn-ghost:hover { border-color: rgba(255,255,255,0.9); color: white; }
.btn-outline { background: transparent; border: 1px solid var(--teal); color: var(--teal); }
.btn-outline:hover { background: var(--teal); color: white; }
.btn-rose { background: var(--rose); border: 1px solid var(--rose); color: white; }
.btn-rose:hover { background: #c08888; box-shadow: 0 12px 32px rgba(212,160,160,0.3); }
.btn-sm { min-height: 38px; padding: 0 18px; font-size: 11px; }

/* ── NAVIGATION ──────────────────────────────────── */
.site-nav { position: fixed; top: 0; left: 0; right: 0; z-index: 200; display: flex; align-items: center; justify-content: space-between; padding: 1.25rem clamp(1.25rem, 5vw, 5rem); transition: all 0.35s ease; }
.site-nav.scrolled { backdrop-filter: blur(14px); background: rgba(250,248,243,0.92); border-bottom: 1px solid rgba(200,168,75,0.5); padding-block: 0.85rem; }
.brand { display: flex; align-items: center; gap: 0.75rem; }
.nav-logo-img { width: 38px; height: 38px; object-fit: contain; }
.brand-name { font-family: var(--display); font-size: 1.35rem; color: white; transition: color 0.35s ease; }
.site-nav.scrolled .brand-name { color: var(--teal-dk); }
.nav-right { display: flex; align-items: center; gap: 2.25rem; }
.nav-links { display: flex; gap: 2.1rem; list-style: none; }
.nav-links a { color: rgba(255,255,255,0.75); font-size: 12px; font-weight: 600; letter-spacing: 1.3px; text-transform: uppercase; position: relative; transition: color 0.2s ease; }
.site-nav.scrolled .nav-links a { color: var(--ink-mid); }
.nav-links a::after { content: ''; position: absolute; bottom: -5px; left: 0; width: 100%; height: 1px; background: var(--gold); transform: scaleX(0); transform-origin: left; transition: transform 0.26s ease; }
.nav-links a:hover { color: var(--gold); }
.nav-links a:hover::after { transform: scaleX(1); }
.nav-actions { display: flex; gap: 0.75rem; }
.nav-cta { min-height: 40px; padding-inline: 22px; border-radius: 4px; }

/* ── HERO ────────────────────────────────────────── */
.hero { display: grid; grid-template-columns: 58% 42%; min-height: 100vh; overflow: hidden; }
.hero-left { position: relative; background: var(--teal-hero); display: flex; align-items: center; min-height: 100vh; padding: 8rem clamp(1.5rem, 6vw, 6rem) 4.5rem; overflow: hidden; }
.hero-left::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: no-repeat; background-position: right center; background-size: auto 80%; opacity: 0.12; }
.hero-left::after { content: ''; position: absolute; bottom: -18rem; left: -10rem; width: 34rem; height: 34rem; border-radius: 50%; background: radial-gradient(circle, rgba(200,168,75,0.15), transparent 64%); animation: orbPulse 8s ease-in-out infinite; }
.hero-copy { position: relative; z-index: 2; max-width: 620px; }
.hero-title { font-family: var(--display); font-size: clamp(4rem, 8vw, 5.8rem); font-weight: 400; line-height: 0.96; color: white; margin-bottom: 1.25rem; }
.hero-title span { display: block; opacity: 0; transform: translateY(24px); }
.hero-title span:nth-child(1) { animation: slideUp 0.75s 0.2s ease forwards; }
.hero-title span:nth-child(2) { animation: slideUp 0.75s 0.4s ease forwards; color: var(--gold); }
.hero-title span:nth-child(3) { animation: slideUp 0.75s 0.6s ease forwards; }
.hero-tagline { font-family: var(--display); font-size: 1.1rem; font-style: italic; color: rgba(255,255,255,0.5); margin-bottom: 1.25rem; opacity: 0; transform: translateY(24px); animation: slideUp 0.75s 0.8s ease forwards; }
.hero-body { font-size: 0.92rem; font-weight: 300; line-height: 1.85; color: rgba(255,255,255,0.6); max-width: 420px; margin-bottom: 2rem; opacity: 0; transform: translateY(24px); animation: slideUp 0.75s 1s ease forwards; }
.hero-actions { display: flex; flex-wrap: wrap; gap: 0.9rem; opacity: 0; transform: translateY(24px); animation: slideUp 0.75s 1.2s ease forwards; }
.hero-proof { position: absolute; bottom: 3rem; left: clamp(1.5rem, 6vw, 6rem); display: flex; align-items: center; gap: 0.95rem; z-index: 2; opacity: 0; transform: translateY(24px); animation: slideUp 0.75s 1.4s ease forwards; }
.avatars { display: flex; }
.avatar { width: 34px; height: 34px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-family: var(--display); font-size: 0.9rem; color: white; margin-left: -8px; }
.avatar:first-child { margin-left: 0; }
.avatar:nth-child(1) { background: var(--teal); }
.avatar:nth-child(2) { background: #557866; }
.avatar:nth-child(3) { background: var(--gold); }
.avatar:nth-child(4) { background: var(--teal-dk); }
.proof-text { font-size: 12.5px; font-weight: 300; color: rgba(255,255,255,0.55); }

.hero-right { position: relative; background: var(--ivory); display: flex; align-items: center; justify-content: center; min-height: 100vh; overflow: hidden; opacity: 0; animation: fadeIn 0.9s 0.9s ease forwards; }
.hero-right::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 400px auto; opacity: 0.06; animation: drift 40s linear infinite; }
.calligraphy-panel { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; text-align: center; width: min(100%, 480px); min-height: 68vh; }
.rule { width: 60px; height: 1px; background: var(--gold); opacity: 0.65; }
.hero-arabic-word { font-family: var(--arabic); font-size: clamp(4.5rem, 10vw, 8rem); font-weight: 700; color: rgba(26,107,114,0.9); line-height: 1; margin: 1rem 0 0.65rem; }
.translation { font-family: var(--display); font-size: 1.1rem; font-style: italic; color: var(--teal-md); margin: 0.85rem 0 1rem; }
.star { width: 28px; height: 28px; color: var(--gold); margin-bottom: auto; }
.right-stats { position: absolute; bottom: 2.3rem; width: 100%; display: flex; justify-content: center; gap: 2.25rem; }
.right-stat strong { display: block; font-family: var(--display); font-size: 3rem; font-weight: 400; color: var(--gold); line-height: 0.9; }
.right-stat span { display: block; font-size: 11px; font-weight: 600; letter-spacing: 1.3px; color: rgba(28,26,23,0.7); margin-top: 0.35rem; text-transform: uppercase; }

/* ── ORNAMENTAL DIVIDER ──────────────────────────── */
.ornament { display: flex; align-items: center; justify-content: center; gap: 1rem; padding: 1rem 0; background: var(--ivory); }
.ornament-line { height: 1px; width: 60px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
.ornament-icon { font-size: 1.2rem; color: var(--gold); opacity: 0.6; }
.ornament svg { width: 20px; height: 20px; color: var(--gold); opacity: 0.5; }

/* ── MARQUEE BAND ────────────────────────────────── */
.band { background: linear-gradient(135deg, var(--gold), var(--gold-lt)); overflow: hidden; padding: 13px 0; position: relative; }
.band::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); animation: shimmer 3s ease-in-out infinite; }
.band-track { display: flex; gap: 2.2rem; width: max-content; position: relative; z-index: 1; animation: marquee 24s linear infinite; }
.band-item { font-size: 12.5px; font-weight: 600; color: var(--teal-dk); letter-spacing: 0.6px; white-space: nowrap; }
@keyframes marquee { to { transform: translateX(-50%); } }
@keyframes shimmer { to { transform: translateX(100%); } }

/* ── STATS BAR ───────────────────────────────────── */
.stats-bar { background: var(--teal-dk); display: grid; grid-template-columns: repeat(3, 1fr); padding: 4rem clamp(1.5rem, 5vw, 5rem); }
.stat { padding: 1.25rem; text-align: center; position: relative; }
.stat + .stat { border-left: 1px solid rgba(200,168,75,0.25); }
.stat-number { font-family: var(--display); font-size: 3.5rem; color: var(--gold-lt); line-height: 1; }
.stat-label { font-size: 11px; font-weight: 600; letter-spacing: 1.5px; color: rgba(255,255,255,0.4); margin-top: 0.55rem; text-transform: uppercase; }

/* ── SECTIONS ────────────────────────────────────── */
.section { padding: 8rem clamp(1.5rem, 5vw, 5rem); position: relative; overflow: hidden; }
.section-inner { margin: 0 auto; max-width: 1200px; position: relative; z-index: 2; }
.eyebrow { display: block; font-size: 11px; font-weight: 600; letter-spacing: 2.4px; color: var(--gold); margin-bottom: 0.95rem; text-transform: uppercase; }
.section-heading { font-family: var(--display); font-size: clamp(2.8rem, 5vw, 4.4rem); font-weight: 400; line-height: 1.06; color: var(--ink); }
.section-heading em { color: var(--teal); font-style: italic; }
.section-heading.light { color: white; }
.section-heading.light em { color: var(--gold); }

/* ── FLOATING PETALS ─────────────────────────────── */
.petal { position: absolute; width: 12px; height: 12px; border-radius: 50% 0 50% 0; opacity: 0.15; pointer-events: none; }
.petal-gold { background: var(--gold); }
.petal-rose { background: var(--rose); }
.petal-teal { background: var(--teal); }
@keyframes petalFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  25% { transform: translateY(-20px) rotate(45deg); }
  50% { transform: translateY(-10px) rotate(90deg); }
  75% { transform: translateY(-25px) rotate(135deg); }
}

/* ── SCROLL REVEAL ───────────────────────────────── */
.reveal { opacity: 0; transform: translateY(30px); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }
.reveal-left { opacity: 0; transform: translateX(-40px); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal-left.visible { opacity: 1; transform: translateX(0); }
.reveal-right { opacity: 0; transform: translateX(40px); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal-right.visible { opacity: 1; transform: translateX(0); }
.reveal-scale { opacity: 0; transform: scale(0.92); transition: opacity 0.7s ease, transform 0.7s ease; }
.reveal-scale.visible { opacity: 1; transform: scale(1); }

/* ── FEATURES ────────────────────────────────────── */
.features { background: var(--ivory); }
.features::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 400px auto; opacity: 0.06; pointer-events: none; }
.features-grid { display: grid; gap: 1.5px; grid-template-columns: repeat(3, 1fr); margin-top: 3.5rem; background: rgba(200,168,75,0.5); }
.feature-card { background: var(--ivory); position: relative; padding: 2.45rem; min-height: 286px; overflow: hidden; transition: background 0.35s ease; }
.feature-card::before { content: ''; position: absolute; bottom: 0; left: 0; width: 3px; height: 0; background: linear-gradient(to top, var(--gold), var(--rose)); transition: height 0.4s cubic-bezier(0.25,0.46,0.45,0.94); }
.feature-card:hover::before { height: 100%; }
.feature-card:hover { background: var(--rose-pale); }
.feature-card.wide { grid-column: span 2; }
.feature-icon { width: 44px; height: 44px; border-radius: 2px; border: 1px solid rgba(26,107,114,0.3); display: flex; align-items: center; justify-content: center; margin-bottom: 1.3rem; }
.feature-icon svg { width: 22px; height: 22px; fill: none; stroke: var(--teal); stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
.feature-title { font-family: var(--display); font-size: 1.55rem; color: var(--ink); margin-bottom: 0.65rem; }
.feature-body { font-size: 0.875rem; font-weight: 300; line-height: 1.85; color: var(--ink-soft); max-width: 560px; }
.feature-tag { display: inline-block; margin-top: 1rem; padding-bottom: 2px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.9px; color: var(--teal); border-bottom: 1px solid currentColor; text-transform: uppercase; transition: letter-spacing 0.28s ease; }
.feature-card:hover .feature-tag { letter-spacing: 1.55px; }

/* ── ARABIC DIVIDER ──────────────────────────────── */
.arabic-divider { background: var(--ivory); padding: 3rem 1.5rem; text-align: center; position: relative; }
.arabic-divider::before { content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 1px; height: 40px; background: linear-gradient(to bottom, var(--gold), transparent); }
.divider-line { width: 80px; height: 1px; background: rgba(200,168,75,0.7); margin: 0 auto 1.2rem; }
.divider-line.bottom { margin: 1rem auto 1.1rem; }
.divider-arabic { display: block; font-size: 2.5rem; letter-spacing: 6px; line-height: 1; margin: 0 auto; opacity: 0.5; color: var(--gold); }
.divider-caption { font-size: 11px; font-weight: 600; letter-spacing: 2.6px; color: rgba(26,107,114,0.4); text-transform: uppercase; }

/* ── HOW IT WORKS ────────────────────────────────── */
.how { background: var(--teal-dk); position: relative; }
.how::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 400px auto; opacity: 0.10; animation: drift 40s linear infinite; }
.steps { display: grid; gap: 3rem; grid-template-columns: repeat(3, 1fr); margin-top: 4rem; position: relative; z-index: 2; }
.step { opacity: 0; transform: translateY(26px); transition: opacity 0.7s ease, transform 0.7s ease; }
.step.visible { opacity: 1; transform: translateY(0); }
.step-number { font-family: var(--display); font-size: 5.5rem; color: rgba(200,168,75,0.15); line-height: 0.9; }
.step-line { height: 1px; background: rgba(200,168,75,0.15); margin: 1.2rem 0 1.25rem; position: relative; width: 100%; }
.step-line::after { content: ''; position: absolute; top: 0; left: 0; height: 1px; width: 0; background: linear-gradient(90deg, rgba(200,168,75,0.6), rgba(212,160,160,0.6)); }
.step.visible .step-line::after { animation: drawLine 0.85s ease forwards; }
.step-title { font-family: var(--display); font-size: 1.65rem; color: white; margin-bottom: 0.7rem; }
.step-body { font-size: 0.875rem; font-weight: 300; line-height: 1.85; color: rgba(255,255,255,0.5); }
@keyframes drawLine { to { width: 60%; } }

/* ── TESTIMONIALS ────────────────────────────────── */
.testimonials { background: var(--white); }
.testimonials::before { content: ''; position: absolute; top: -100px; right: -100px; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(212,160,160,0.08), transparent 70%); pointer-events: none; }
.testimonial-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(3, 1fr); margin-top: 3.5rem; }
.testimonial { background: var(--ivory); border: 1px solid rgba(200,168,75,0.2); border-radius: 3px; min-height: 300px; padding: 2rem; position: relative; overflow: hidden; transition: transform 0.35s ease, box-shadow 0.35s ease; }
.testimonial:hover { transform: translateY(-4px); box-shadow: 0 20px 50px rgba(26,107,114,0.08); }
.testimonial::after { content: ''; position: absolute; top: 0; right: 0; width: 60px; height: 60px; background: linear-gradient(135deg, rgba(212,160,160,0.1), transparent); pointer-events: none; }
.quote-mark { position: absolute; right: 1.2rem; top: 0.2rem; font-family: var(--display); font-size: 7rem; color: rgba(200,168,75,0.1); line-height: 1; }
.quote { font-family: var(--display); font-size: 1.05rem; font-style: italic; line-height: 1.8; color: var(--ink-mid); margin-bottom: 1.6rem; position: relative; z-index: 1; }
.author { display: flex; align-items: center; gap: 0.75rem; }
.author-initials { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--teal), var(--rose)); display: flex; align-items: center; justify-content: center; font-family: var(--display); color: white; }
.author-name { font-size: 13px; font-weight: 600; color: var(--ink); }
.author-location { font-size: 12px; font-weight: 300; color: var(--ink-soft); }

/* ── CTA ─────────────────────────────────────────── */
.cta { background: linear-gradient(135deg, var(--gold-pale), var(--rose-pale)); overflow: hidden; text-align: center; position: relative; }
.cta::before { content: ''; position: absolute; inset: 0; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 400px auto; opacity: 0.06; pointer-events: none; }
.cta-inner { position: relative; z-index: 2; margin: 0 auto; max-width: 680px; }
.cta-arabic { font-family: var(--arabic); font-size: 1.3rem; line-height: 1.5; color: var(--teal-md); margin-bottom: 0.2rem; }
.cta-translation { font-size: 12px; font-weight: 300; font-style: italic; color: var(--ink-soft); margin-bottom: 1rem; }
.cta-eyebrow { font-family: var(--display); font-size: 1.2rem; font-style: italic; color: var(--teal); margin-bottom: 0.75rem; }
.cta-heading { font-family: var(--display); font-size: clamp(3rem, 6vw, 4.7rem); font-weight: 400; line-height: 1.02; color: var(--teal-dk); margin-bottom: 1.25rem; }
.cta-heading em { color: var(--gold); font-style: italic; }
.cta-body { font-size: 0.9rem; font-weight: 300; line-height: 1.85; color: var(--ink-soft); margin-bottom: 2rem; }
.cta-actions { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; }

/* ── FEMININE DECORATIVE WAVE ────────────────────── */
.wave-divider { position: relative; height: 60px; overflow: hidden; }
.wave-divider svg { position: absolute; bottom: 0; width: 100%; }

/* ── FOOTER ──────────────────────────────────────── */
.footer { background: var(--ink); padding: 5.5rem clamp(1.5rem, 5vw, 5rem) 2rem; }
.footer-inner { margin: 0 auto; max-width: 1200px; }
.footer-top { display: grid; gap: 4rem; grid-template-columns: 1.55fr 1fr 1fr 1fr; padding-bottom: 3.8rem; border-bottom: 1px solid rgba(255,255,255,0.07); }
.footer-brand-row { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.4rem; }
.footer-brand { font-family: var(--display); font-size: 1.6rem; color: white; }
.footer-tagline { font-family: var(--display); font-size: 1rem; font-style: italic; color: var(--gold); margin-bottom: 0.55rem; }
.footer-desc { font-size: 0.85rem; font-weight: 300; line-height: 1.8; color: rgba(255,255,255,0.35); max-width: 270px; margin-bottom: 1rem; }
.footer-heading { font-size: 10.5px; font-weight: 600; letter-spacing: 2px; color: rgba(255,255,255,0.28); margin-bottom: 1.15rem; text-transform: uppercase; }
.footer-links { list-style: none; }
.footer-links li + li { margin-top: 0.65rem; }
.footer-links a { font-size: 0.86rem; font-weight: 300; color: rgba(255,255,255,0.42); transition: color 0.2s ease; }
.footer-links a:hover { color: var(--gold); }
.footer-bottom { display: flex; align-items: center; justify-content: space-between; padding-top: 2rem; font-size: 12px; font-weight: 300; color: rgba(255,255,255,0.3); }
.footer-bottom a { margin-left: 1.5rem; color: rgba(255,255,255,0.3); transition: color 0.2s ease; }
.footer-bottom a:hover { color: var(--gold); }

/* ── KEYFRAMES ───────────────────────────────────── */
@keyframes drift { to { background-position: 120px 120px; } }
@keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { to { opacity: 1; } }
@keyframes orbPulse { 50% { transform: scale(1.08); } }
@keyframes sparkle {
  0%, 100% { opacity: 0; transform: scale(0); }
  50% { opacity: 1; transform: scale(1); }
}

/* ── RESPONSIVE ──────────────────────────────────── */
@media (max-width: 1080px) {
  .hero { grid-template-columns: 1fr; }
  .hero-left, .hero-right { min-height: auto; }
  .hero-left { min-height: 760px; padding-bottom: 8rem; }
  .hero-right { min-height: 560px; padding-block: 5rem 3.5rem; }
  .calligraphy-panel { min-height: 450px; }
  .right-stats { position: static; margin-top: 3rem; }
  .star { margin-bottom: 0; }
  .footer-top { gap: 3rem; grid-template-columns: 1.25fr repeat(3, 1fr); }
}
@media (max-width: 820px) {
  html { scroll-padding-top: 74px; }
  .site-nav, .site-nav.scrolled { padding: 0.85rem 1.25rem; }
  .nav-links { display: none; }
  .brand-name { font-size: 1.15rem; }
  .nav-logo-img { width: 32px; height: 32px; }
  .nav-right { gap: 0.75rem; }
  .nav-cta { font-size: 11px; min-height: 38px; padding-inline: 16px; }
  .hero-left { min-height: 720px; padding-top: 7rem; }
  .hero-proof { bottom: 2rem; left: 1.5rem; right: 1.5rem; }
  .hero-right { min-height: 500px; }
  .calligraphy-panel { min-height: 390px; }
  .stats-bar, .features-grid, .steps, .testimonial-grid, .footer-top { grid-template-columns: 1fr; }
  .features-grid { margin-top: 2.5rem; }
  .feature-card.wide { grid-column: span 1; }
  .steps { gap: 2.25rem; margin-top: 3rem; }
  .testimonial-grid { margin-top: 2.5rem; }
  .stat + .stat { border-left: 0; border-top: 1px solid rgba(200,168,75,0.2); }
  .footer { padding-top: 4rem; }
  .footer-top { gap: 2rem; padding-bottom: 2.5rem; }
  .footer-bottom { flex-direction: column; gap: 1rem; align-items: flex-start; }
  .footer-bottom a { margin-left: 0; margin-right: 1rem; }
}
@media (max-width: 560px) {
  .preloader-card { padding: 2.7rem 1.4rem; width: calc(100vw - 2rem); }
  .preloader-arabic { font-size: clamp(2.8rem, 16vw, 4rem); }
  .preloader-name { font-size: 1.35rem; }
  .brand { gap: 0.55rem; min-width: 0; }
  .brand-name { font-size: 1.02rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .nav-cta { font-size: 10px; letter-spacing: 0.6px; min-height: 36px; padding-inline: 12px; }
  .hero-left { min-height: 700px; padding: 6.5rem 1.25rem 8rem; }
  .hero-title { font-size: clamp(3.1rem, 16vw, 3.75rem); line-height: 1; }
  .hero-actions, .cta-actions { flex-direction: column; align-items: stretch; }
  .btn { width: 100%; }
  .hero-proof { flex-direction: column; align-items: flex-start; }
  .hero-right { min-height: 440px; padding: 4rem 1.25rem 2.5rem; }
  .calligraphy-panel { min-height: 340px; }
  .hero-arabic-word { font-size: 4.2rem; }
  .right-stats { display: grid; gap: 0.85rem; grid-template-columns: repeat(3, 1fr); margin-top: 2.3rem; }
  .right-stat strong { font-size: 2.25rem; }
  .right-stat span { font-size: 9px; letter-spacing: 0.9px; }
  .stats-bar { padding-block: 2.75rem; }
  .stat-number { font-size: 3rem; }
  .section { padding-block: 5.5rem; }
  .section-heading { font-size: clamp(2.65rem, 14vw, 3.35rem); }
  .feature-card { min-height: auto; padding: 2rem 1.45rem; }
  .arabic-divider { padding-block: 2.5rem; }
  .divider-arabic { font-size: 2rem; letter-spacing: 4px; }
  .step-number { font-size: 4.4rem; }
  .testimonial { min-height: auto; padding: 1.75rem 1.45rem; }
  .quote-mark { font-size: 5.8rem; }
  .cta-heading { font-size: clamp(2.85rem, 15vw, 3.7rem); }
  .footer { padding-inline: 1.25rem; }
}
@media (max-width: 390px) {
  .site-nav, .site-nav.scrolled { padding-inline: 1rem; }
  .nav-logo-img { width: 28px; height: 28px; }
  .brand-name { font-size: 0.94rem; max-width: 142px; }
  .nav-cta { padding-inline: 10px; }
  .hero-left { padding-inline: 1rem; }
  .right-stats { grid-template-columns: 1fr; }
  .right-stat span { font-size: 10px; }
  .band-track { gap: 1.4rem; }
}
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: 0.01ms !important; }
}
</style>
</head>
<body class="loading">

{{-- Preloader --}}
<div class="preloader" id="preloader">
  <div class="preloader-card">
    <img src="{{ asset('images/img1.png') }}" alt="TMC" class="preloader-mark">
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
    <img src="{{ asset('images/img1.png') }}" alt="TMC" class="nav-logo-img">
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
      <a class="btn btn-gold btn-sm nav-cta" href="{{ url('/register') }}">Register</a>
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
        <a class="btn btn-rose" href="{{ url('/register') }}">Create Your Account</a>
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
          <img src="{{ asset('images/img1.png') }}" alt="TMC" style="height:44px;object-fit:contain;">
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

<script>
const nav = document.getElementById('site-nav');
const progress = document.getElementById('progress');
const year = document.getElementById('year');
const preloader = document.getElementById('preloader');
let preloaderHidden = false;
year.textContent = new Date().getFullYear();

function hidePreloader() {
  if (!preloader || preloaderHidden) return;
  preloaderHidden = true;
  document.body.classList.remove('loading');
  preloader.classList.add('hidden');
  setTimeout(() => preloader.remove(), 850);
}
window.addEventListener('load', () => setTimeout(hidePreloader, 650));
setTimeout(hidePreloader, 2600);

function updateChrome() {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  const pct = max > 0 ? (window.scrollY / max) * 100 : 0;
  nav.classList.toggle('scrolled', window.scrollY > 60);
  progress.style.width = pct + '%';
}
updateChrome();
window.addEventListener('scroll', updateChrome, { passive: true });

// Band marquee
const bandItems = ['Daily Reflections', 'Jannah Coins', 'Private Journal', 'Member Souq', 'Halaqahs', 'Legacy Card', 'Faith Community'];
const bandTrack = document.getElementById('band-track');
const bandHtml = bandItems.map(item => `<span class="band-item">\u2726 ${item}</span>`).join('');
bandTrack.innerHTML = bandHtml + bandHtml;

// Scroll reveal
const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('visible');
    revealObserver.unobserve(entry.target);
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => revealObserver.observe(el));

// Steps stagger
const stepObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const steps = Array.from(document.querySelectorAll('.step'));
    const delay = steps.indexOf(entry.target) * 200;
    setTimeout(() => entry.target.classList.add('visible'), delay);
    stepObserver.unobserve(entry.target);
  });
}, { threshold: 0.2 });
document.querySelectorAll('.step').forEach(step => stepObserver.observe(step));

// Stat counters
const statObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target;
    const target = Number(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    const duration = 1800;
    const start = performance.now();
    function tick(now) {
      const p = Math.min((now - start) / duration, 1);
      el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target) + suffix;
      if (p < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    statObserver.unobserve(el);
  });
}, { threshold: 0.5 });
document.querySelectorAll('[data-count]').forEach(el => statObserver.observe(el));

// Custom cursor
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
if (finePointer) {
  const dot = document.getElementById('cursor-dot');
  const ring = document.getElementById('cursor-ring');
  let mx = innerWidth / 2, my = innerHeight / 2, rx = mx, ry = my;
  document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; dot.style.left = mx + 'px'; dot.style.top = my + 'px'; });
  (function loop() { rx += (mx - rx) * 0.12; ry += (my - ry) * 0.12; ring.style.left = rx + 'px'; ring.style.top = ry + 'px'; requestAnimationFrame(loop); })();
  document.querySelectorAll('a, button').forEach(el => {
    el.addEventListener('mouseenter', () => { dot.style.transform = 'translate(-50%,-50%) scale(1.65)'; ring.style.width = '48px'; ring.style.height = '48px'; });
    el.addEventListener('mouseleave', () => { dot.style.transform = 'translate(-50%,-50%) scale(1)'; ring.style.width = '32px'; ring.style.height = '32px'; });
  });
}
</script>
</body>
</html>
