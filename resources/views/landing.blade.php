<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>The Muhsinat Club</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Dancing+Script:wght@400&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet">
<!-- CHANGE 1: favicon -->
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
html { scroll-behavior: smooth; scroll-padding-top: 82px; }
body { background: var(--ivory); color: var(--ink); font-family: var(--body); overflow-x: hidden; text-rendering: optimizeLegibility; }
body.loading { overflow: hidden; }
a, button { color: inherit; }
button, a { cursor: pointer; }
.ar { direction: rtl; font-family: var(--arabic); unicode-bidi: embed; }

@media (hover: hover) and (pointer: fine) {
  body, a, button { cursor: none; }
}

.progress { background: linear-gradient(90deg, var(--gold), var(--gold-lt)); height: 2px; left: 0; position: fixed; top: 0; width: 0; z-index: 300; }
.cursor-dot, .cursor-ring { display: none; pointer-events: none; position: fixed; transform: translate(-50%, -50%); }
.cursor-dot { background: var(--gold); border-radius: 50%; height: 8px; width: 8px; z-index: 9999; }
.cursor-ring { border: .5px solid rgba(200,168,75,.78); border-radius: 50%; height: 32px; transition: height .22s ease, width .22s ease; width: 32px; z-index: 9998; }
@media (hover: hover) and (pointer: fine) {
  .cursor-dot, .cursor-ring { display: block; }
}

.preloader { align-items: center; background: var(--teal-hero); display: flex; inset: 0; justify-content: center; opacity: 1; overflow: hidden; position: fixed; transition: opacity .75s ease, visibility .75s ease; visibility: visible; z-index: 10000; }
.preloader::before { animation: patternDrift 25s linear infinite; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23C8A84B' stroke-width='1' opacity='.12'%3E%3Cpath d='M60 8 112 60 60 112 8 60Z'/%3E%3Ccircle cx='60' cy='60' r='31'/%3E%3Cpath d='M60 29 91 60 60 91 29 60Z'/%3E%3Cpath d='M31 9 60 38 89 9M111 31 82 60 111 89M89 111 60 82 31 111M9 89 38 60 9 31'/%3E%3C/g%3E%3C/svg%3E"); content: ''; inset: 0; position: absolute; }
.preloader::after { animation: preloaderGlow 5s ease-in-out infinite; background: radial-gradient(circle, rgba(200,168,75,.2), transparent 62%); border-radius: 50%; content: ''; height: 38rem; position: absolute; width: 38rem; }
.preloader.hidden { opacity: 0; pointer-events: none; visibility: hidden; }
.preloader-card { align-items: center; border: 1px solid rgba(200,168,75,.28); display: flex; flex-direction: column; gap: .8rem; max-width: calc(100vw - 2rem); padding: 3rem clamp(2rem, 5vw, 4.5rem); position: relative; text-align: center; z-index: 1; }
.preloader-card::before, .preloader-card::after { background: var(--gold); content: ''; height: 1px; opacity: .72; position: absolute; width: 68px; }
.preloader-card::before { top: 1.25rem; }
.preloader-card::after { bottom: 1.25rem; }
.preloader-mark { align-items: center; border: 1px solid rgba(200,168,75,.45); border-radius: 50%; color: var(--gold-lt); display: flex; font-size: 1.35rem; height: 46px; justify-content: center; margin-bottom: .2rem; width: 46px; }
.preloader-arabic { color: rgba(200,168,75,.9); font-size: clamp(3rem, 9vw, 5.8rem); font-weight: 700; line-height: 1; }
.preloader-name { color: var(--white); font-family: var(--display); font-size: 1.55rem; line-height: 1; }
.preloader-tag { color: rgba(255,255,255,.52); font-family: var(--display); font-size: 1rem; font-style: italic; }
.preloader-line { background: rgba(255,255,255,.12); height: 1px; margin-top: .8rem; overflow: hidden; position: relative; width: min(220px, 54vw); }
.preloader-line::after { animation: preloaderLine 1.45s ease-in-out infinite; background: linear-gradient(90deg, transparent, var(--gold), transparent); content: ''; height: 100%; left: 0; position: absolute; top: 0; transform: translateX(-100%); width: 70%; }
@keyframes preloaderGlow { 50% { transform: scale(1.08); } }
@keyframes preloaderLine { to { transform: translateX(170%); } }

.btn { align-items: center; border-radius: 4px; display: inline-flex; font-family: var(--body); font-size: 12px; font-weight: 600; justify-content: center; letter-spacing: .9px; min-height: 46px; overflow: hidden; padding: 0 28px; position: relative; text-decoration: none; text-transform: uppercase; transition: border-color .28s ease, box-shadow .28s ease, color .28s ease, transform .28s ease, background .28s ease; }
.btn::after { background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent); content: ''; inset: 0; position: absolute; transform: translateX(-105%); transition: transform .55s ease; }
.btn:hover::after { transform: translateX(105%); }
.btn:hover { transform: translateY(-2px); }
.btn-gold { background: var(--gold); border: 1px solid var(--gold); color: var(--teal-hero); }
.btn-gold:hover { background: var(--gold-lt); box-shadow: 0 18px 42px rgba(200,168,75,.25); }
.btn-ghost { background: transparent; border: 1px solid rgba(255,255,255,.48); color: rgba(255,255,255,.86); }
.btn-ghost:hover { border-color: rgba(255,255,255,.9); color: var(--white); }
.btn-outline { background: transparent; border: 1px solid var(--teal); color: var(--teal); }
.btn-outline:hover { background: var(--teal); color: var(--white); }

.site-nav { align-items: center; display: flex; justify-content: space-between; left: 0; padding: 1.25rem clamp(1.25rem, 5vw, 5rem); position: fixed; right: 0; top: 0; transition: background .35s ease, border .35s ease, padding .35s ease, backdrop-filter .35s ease; z-index: 200; }
.site-nav.scrolled { backdrop-filter: blur(14px); background: rgba(250,248,243,.92); border-bottom: 1px solid rgba(200,168,75,.55); padding-block: .85rem; }
.brand { align-items: center; display: flex; gap: .75rem; text-decoration: none; }
.logo-mark { align-items: center; background: var(--teal); border: 1px solid rgba(200,168,75,.32); border-radius: 50%; color: var(--gold-lt); display: flex; font-size: 1.1rem; height: 40px; justify-content: center; line-height: 1; width: 40px; }
/* CHANGE 2: nav logo image */
.nav-logo-img { height: 38px; object-fit: contain; width: 38px; }
.brand-name { color: var(--white); font-family: var(--display); font-size: 1.35rem; transition: color .35s ease; }
.site-nav.scrolled .brand-name { color: var(--teal-dk); }
.nav-right { align-items: center; display: flex; gap: 2.25rem; }
.nav-links { display: flex; gap: 2.1rem; list-style: none; }
.nav-links a { color: rgba(255,255,255,.76); font-size: 12px; font-weight: 600; letter-spacing: 1.3px; position: relative; text-decoration: none; text-transform: uppercase; transition: color .2s ease; }
.site-nav.scrolled .nav-links a { color: var(--ink-mid); }
.nav-links a::after { background: var(--gold); bottom: -5px; content: ''; height: 1px; left: 0; position: absolute; transform: scaleX(0); transform-origin: left; transition: transform .26s ease; width: 100%; }
.nav-links a:hover::after { transform: scaleX(1); }
.nav-links a:hover { color: var(--gold); }
.nav-cta { border-radius: 4px; min-height: 40px; padding-inline: 22px; }

.pattern-gold, .pattern-teal { position: relative; }
.pattern-gold::before, .pattern-teal::before { background-position: 0 0; content: ''; inset: 0; pointer-events: none; position: absolute; }
.pattern-gold::before { animation: patternDrift 25s linear infinite; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23C8A84B' stroke-width='1' opacity='.14'%3E%3Cpath d='M60 8 112 60 60 112 8 60Z'/%3E%3Ccircle cx='60' cy='60' r='31'/%3E%3Cpath d='M60 29 91 60 60 91 29 60Z'/%3E%3Cpath d='M31 9 60 38 89 9M111 31 82 60 111 89M89 111 60 82 31 111M9 89 38 60 9 31'/%3E%3C/g%3E%3C/svg%3E"); }
.pattern-teal::before { animation: patternDrift 25s linear infinite; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='112' height='112' viewBox='0 0 112 112'%3E%3Cg fill='none' stroke='%231A6B72' stroke-width='1' opacity='.07'%3E%3Cpath d='M56 7 105 56 56 105 7 56Z'/%3E%3Ccircle cx='56' cy='56' r='29'/%3E%3Cpath d='M56 27 85 56 56 85 27 56Z'/%3E%3Cpath d='M28 7 56 35 84 7M105 28 77 56 105 84M84 105 56 77 28 105M7 84 35 56 7 28'/%3E%3C/g%3E%3C/svg%3E"); }
@keyframes patternDrift { to { background-position: 120px 120px; } }

/* CHANGE 5: features section img4 pattern overrides pattern-teal for this section only */
.features::before { animation: patternDrift 40s linear infinite; background-image: url('{{ asset('images/img4.png') }}'); background-repeat: repeat; background-size: 500px auto; content: ''; inset: 0; opacity: 0.4; pointer-events: none; position: absolute; z-index: 0; }

.hero { display: grid; grid-template-columns: 60% 40%; min-height: 100vh; overflow: hidden; }
.hero-left { align-items: center; background: var(--teal-hero); display: flex; min-height: 100vh; overflow: hidden; padding: 8rem clamp(1.5rem, 6vw, 6rem) 4.5rem; position: relative; }
.hero-left::after { animation: orbPulse 8s ease-in-out infinite; background: radial-gradient(circle, rgba(200,168,75,.18), transparent 64%); border-radius: 50%; bottom: -18rem; content: ''; height: 34rem; left: -10rem; position: absolute; width: 34rem; }
.bismillah { color: rgba(200,168,75,.06); font-size: clamp(6rem, 12vw, 11rem); left: 50%; line-height: 1; pointer-events: none; position: absolute; text-align: center; top: 45%; transform: translate(-50%, -50%); white-space: nowrap; z-index: 1; }
.hero-copy { max-width: 620px; position: relative; z-index: 2; }
.hero-title { color: var(--white); font-family: var(--display); font-size: clamp(4.2rem, 8vw, 5.8rem); font-weight: 400; line-height: .96; margin-bottom: 1.25rem; }
.hero-title span { display: block; opacity: 0; transform: translateY(24px); }
.hero-title span:nth-child(1) { animation: slideUp .75s .2s ease forwards; }
.hero-title span:nth-child(2) { animation: slideUp .75s .4s ease forwards; color: var(--gold); }
.hero-title span:nth-child(3) { animation: slideUp .75s .6s ease forwards; }
.hero-tagline { animation: slideUp .75s .8s ease forwards; color: rgba(255,255,255,.55); font-family: var(--display); font-size: 1.1rem; font-style: italic; margin-bottom: 1.25rem; opacity: 0; transform: translateY(24px); }
.hero-body { animation: slideUp .75s 1s ease forwards; color: rgba(255,255,255,.65); font-size: .92rem; font-weight: 300; line-height: 1.85; margin-bottom: 2rem; max-width: 420px; opacity: 0; transform: translateY(24px); }
.hero-actions { animation: slideUp .75s 1.2s ease forwards; display: flex; flex-wrap: wrap; gap: .9rem; opacity: 0; transform: translateY(24px); }
.hero-proof { align-items: center; animation: slideUp .75s 1.4s ease forwards; bottom: 3rem; display: flex; gap: .95rem; left: clamp(1.5rem, 6vw, 6rem); opacity: 0; position: absolute; transform: translateY(24px); z-index: 2; }
.avatars { display: flex; }
.avatar { align-items: center; border: 2px solid rgba(255,255,255,.28); border-radius: 50%; color: var(--white); display: flex; font-family: var(--display); height: 34px; justify-content: center; margin-left: -8px; width: 34px; }
.avatar:first-child { margin-left: 0; }
.avatar:nth-child(1) { background: #1A6B72; }
.avatar:nth-child(2) { background: #557866; }
.avatar:nth-child(3) { background: #9A7D38; }
.avatar:nth-child(4) { background: #0D3F44; }
.proof-text { color: rgba(255,255,255,.58); font-size: 12.5px; font-weight: 300; }
.hero-right { align-items: center; animation: fadeIn .9s .9s ease forwards; background: var(--ivory); display: flex; justify-content: center; min-height: 100vh; opacity: 0; overflow: hidden; padding: 8rem 2rem 3rem; position: relative; }
.hero-right::before { opacity: .86; }
.calligraphy-panel { align-items: center; display: flex; flex-direction: column; min-height: 68vh; position: relative; text-align: center; width: min(100%, 480px); z-index: 2; }
.rule { background: var(--gold); height: 1px; opacity: .65; width: 60px; }
.hero-arabic-word { color: rgba(26,107,114,.9); font-size: clamp(4.5rem, 10vw, 8rem); font-weight: 700; line-height: 1; margin: 1rem 0 .65rem; }
.translation { color: var(--teal-md); font-family: var(--display); font-size: 1.1rem; font-style: italic; margin: .85rem 0 1rem; }
.star { color: var(--gold); height: 28px; margin-bottom: auto; width: 28px; }
.right-stats { align-items: center; bottom: 2.3rem; display: flex; gap: 2.25rem; justify-content: center; position: absolute; width: 100%; }
.right-stat strong { color: var(--gold); display: block; font-family: var(--display); font-size: 3rem; font-weight: 400; line-height: .9; }
.right-stat span { color: rgba(28,26,23,.72); display: block; font-size: 11px; font-weight: 600; letter-spacing: 1.3px; margin-top: .35rem; text-transform: uppercase; }
@keyframes orbPulse { 50% { transform: scale(1.08); } }
@keyframes slideUp { to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { to { opacity: 1; } }

.band { background: var(--gold); overflow: hidden; padding: 13px 0; position: relative; }
.band::after { animation: shimmer 3s ease-in-out infinite; background: linear-gradient(90deg, transparent, rgba(255,255,255,.2), transparent); content: ''; inset: 0; position: absolute; transform: translateX(-100%); }
.band-track { animation: marquee 24s linear infinite; display: flex; gap: 2.2rem; position: relative; width: max-content; z-index: 1; }
.band-item { color: var(--teal-dk); font-size: 12.5px; font-weight: 600; letter-spacing: .6px; white-space: nowrap; }
@keyframes marquee { to { transform: translateX(-50%); } }
@keyframes shimmer { to { transform: translateX(100%); } }

.stats-bar { background: var(--teal-dk); display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); padding: 4rem clamp(1.5rem, 5vw, 5rem); }
.stat { padding: 1.25rem; position: relative; text-align: center; }
.stat + .stat { border-left: 1px solid rgba(200,168,75,.28); }
.stat-number { color: var(--gold-lt); font-family: var(--display); font-size: 3.5rem; line-height: 1; }
.stat-label { color: rgba(255,255,255,.45); font-size: 11px; font-weight: 600; letter-spacing: 1.5px; margin-top: .55rem; text-transform: uppercase; }

.section { padding: 8rem clamp(1.5rem, 5vw, 5rem); position: relative; }
.section-inner { margin: 0 auto; max-width: 1200px; position: relative; z-index: 2; }
.eyebrow { color: var(--gold); display: block; font-size: 11px; font-weight: 600; letter-spacing: 2.4px; margin-bottom: .95rem; text-transform: uppercase; }
.section-heading { color: var(--ink); font-family: var(--display); font-size: clamp(2.8rem, 5vw, 4.4rem); font-weight: 400; line-height: 1.06; }
.section-heading em { color: var(--teal); font-style: italic; }
.section-heading.light { color: var(--white); }
.section-heading.light em { color: var(--gold); }
.fade-up { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
.fade-up.visible { opacity: 1; transform: translateY(0); }

.features { background: var(--ivory); overflow: hidden; }
.features-grid { background: rgba(200,168,75,.55); display: grid; gap: 1.5px; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 3.5rem; }
.feature-card { background: var(--ivory); min-height: 286px; opacity: 0; overflow: hidden; padding: 2.45rem; position: relative; transform: translateY(22px); transition: background .35s ease, opacity .6s ease, transform .6s ease; }
.feature-card.visible { opacity: 1; transform: translateY(0); }
.feature-card.wide { grid-column: span 2; }
.feature-card::before { background: var(--gold); bottom: 0; content: ''; height: 0; left: 0; position: absolute; transition: height .4s cubic-bezier(.25,.46,.45,.94); width: 3px; }
.feature-card:hover::before { height: 100%; }
.feature-card:hover { background: var(--teal-lt); }
.feature-icon { align-items: center; border: 1px solid rgba(26,107,114,.32); border-radius: 2px; display: flex; height: 44px; justify-content: center; margin-bottom: 1.3rem; width: 44px; }
.feature-icon svg { fill: none; height: 22px; stroke: var(--teal); stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; width: 22px; }
.feature-title { color: var(--ink); font-family: var(--display); font-size: 1.55rem; font-weight: 400; margin-bottom: .65rem; }
.feature-body { color: var(--ink-soft); font-size: .875rem; font-weight: 300; line-height: 1.85; max-width: 560px; }
.feature-tag { border-bottom: 1px solid currentColor; color: var(--teal); display: inline-block; font-size: 10.5px; font-weight: 600; letter-spacing: .9px; margin-top: 1rem; padding-bottom: 2px; text-transform: uppercase; transition: letter-spacing .28s ease; }
.feature-card:hover .feature-tag { letter-spacing: 1.55px; }

.arabic-divider { background: var(--ivory); padding: 3rem 1.5rem; text-align: center; }
.divider-line { background: rgba(200,168,75,.7); height: 1px; margin: 0 auto 1.2rem; width: 80px; }
.divider-line.bottom { margin: 1rem auto 1.1rem; }
.divider-arabic { color: var(--gold); display: block; font-size: 2.5rem; letter-spacing: 6px; line-height: 1; margin: 0 auto; opacity: .5; }
.divider-caption { color: rgba(26,107,114,.4); font-size: 11px; font-weight: 600; letter-spacing: 2.6px; text-transform: uppercase; }

.how { background: var(--teal-dk); overflow: hidden; }
.how::before { opacity: .74; }
.steps { display: grid; gap: 3rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 4rem; }
.step { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
.step.visible { opacity: 1; transform: translateY(0); }
.step-number { color: rgba(200,168,75,.18); font-family: var(--display); font-size: 5.5rem; line-height: .9; }
.step-line { background: rgba(200,168,75,.18); height: 1px; margin: 1.2rem 0 1.25rem; position: relative; width: 100%; }
.step-line::after { background: rgba(200,168,75,.66); content: ''; height: 1px; left: 0; position: absolute; top: 0; width: 0; }
.step.visible .step-line::after { animation: drawLine .85s ease forwards; }
.step-title { color: var(--white); font-family: var(--display); font-size: 1.65rem; font-weight: 400; margin-bottom: .7rem; }
.step-body { color: rgba(255,255,255,.55); font-size: .875rem; font-weight: 300; line-height: 1.85; }
@keyframes drawLine { to { width: 60%; } }

.testimonials { background: var(--white); }
.testimonial-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 3.5rem; }
.testimonial { background: var(--ivory); border: 1px solid rgba(200,168,75,.22); border-radius: 3px; min-height: 300px; opacity: 0; overflow: hidden; padding: 2rem; position: relative; transform: translateY(24px); transition: opacity .65s ease, transform .35s ease, box-shadow .35s ease; }
.testimonial.visible { opacity: 1; transform: translateY(0); }
.testimonial:hover { box-shadow: 0 20px 50px rgba(26,107,114,.1); transform: translateY(-4px); }
.quote-mark { color: rgba(200,168,75,.1); font-family: var(--display); font-size: 7rem; line-height: 1; position: absolute; right: 1.2rem; top: .2rem; }
.quote { color: var(--ink-mid); font-family: var(--display); font-size: 1.05rem; font-style: italic; line-height: 1.8; margin-bottom: 1.6rem; position: relative; z-index: 1; }
.author { align-items: center; display: flex; gap: .75rem; }
.author-initials { align-items: center; background: var(--teal); border-radius: 50%; color: var(--white); display: flex; font-family: var(--display); height: 38px; justify-content: center; width: 38px; }
.author-name { color: var(--ink); font-size: 13px; font-weight: 600; }
.author-location { color: var(--ink-soft); font-size: 12px; font-weight: 300; }

.cta { background: var(--gold-pale); overflow: hidden; text-align: center; }
.cta::before { opacity: .82; }
.cta-inner { margin: 0 auto; max-width: 680px; position: relative; z-index: 2; }
.cta-arabic { color: var(--teal-md); font-size: 1.3rem; line-height: 1.5; margin-bottom: .2rem; text-align: center; }
.cta-translation { color: var(--ink-soft); font-size: 12px; font-style: italic; font-weight: 300; margin-bottom: 1rem; }
.cta-eyebrow { color: var(--teal); font-family: var(--display); font-size: 1.2rem; font-style: italic; margin-bottom: .75rem; }
.cta-heading { color: var(--teal-dk); font-family: var(--display); font-size: clamp(3rem, 6vw, 4.7rem); font-weight: 400; line-height: 1.02; margin-bottom: 1.25rem; }
.cta-heading em { color: var(--gold); font-style: italic; }
.cta-body { color: var(--ink-soft); font-size: .9rem; font-weight: 300; line-height: 1.85; margin-bottom: 2rem; }
.cta-actions { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: center; }

.footer { background: var(--ink); padding: 5.5rem clamp(1.5rem, 5vw, 5rem) 2rem; }
.footer-inner { margin: 0 auto; max-width: 1200px; }
.footer-top { border-bottom: 1px solid rgba(255,255,255,.07); display: grid; gap: 4rem; grid-template-columns: 1.55fr 1fr 1fr 1fr; padding-bottom: 3.8rem; }
.footer-brand-row { align-items: center; display: flex; gap: .75rem; margin-bottom: .4rem; }
.footer-brand { color: var(--white); font-family: var(--display); font-size: 1.6rem; }
.footer-tagline { color: var(--gold); font-family: var(--display); font-size: 1rem; font-style: italic; margin-bottom: .55rem; }
.footer-arabic { color: rgba(200,168,75,.45); font-size: 1rem; margin-bottom: 1rem; }
.footer-desc { color: rgba(255,255,255,.38); font-size: .85rem; font-weight: 300; line-height: 1.8; max-width: 270px; }
.footer-heading { color: rgba(255,255,255,.3); font-size: 10.5px; font-weight: 600; letter-spacing: 2px; margin-bottom: 1.15rem; text-transform: uppercase; }
.footer-links { list-style: none; }
.footer-links li + li { margin-top: .65rem; }
.footer-links a { color: rgba(255,255,255,.45); font-size: .86rem; font-weight: 300; text-decoration: none; transition: color .2s ease; }
.footer-links a:hover { color: var(--gold); }
.footer-bottom { align-items: center; color: rgba(255,255,255,.32); display: flex; font-size: 12px; font-weight: 300; justify-content: space-between; padding-top: 2rem; }
.footer-bottom a { color: rgba(255,255,255,.32); margin-left: 1.5rem; text-decoration: none; transition: color .2s ease; }
.footer-bottom a:hover { color: var(--gold); }

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
  .site-nav, .site-nav.scrolled { padding: .85rem 1.25rem; }
  .nav-links { display: none; }
  .brand-name { font-size: 1.15rem; }
  .nav-logo-img { height: 32px; width: 32px; }
  .nav-right { gap: .75rem; }
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
  .stat + .stat { border-left: 0; border-top: 1px solid rgba(200,168,75,.22); }
  .footer { padding-top: 4rem; }
  .footer-top { gap: 2rem; padding-bottom: 2.5rem; }
  .footer-bottom { align-items: flex-start; flex-direction: column; gap: 1rem; }
  .footer-bottom a { margin-left: 0; margin-right: 1rem; }
}

@media (max-width: 560px) {
  .preloader-card { padding: 2.7rem 1.4rem; width: calc(100vw - 2rem); }
  .preloader-arabic { font-size: clamp(2.8rem, 16vw, 4rem); }
  .preloader-name { font-size: 1.35rem; }
  .brand { gap: .55rem; min-width: 0; }
  .brand-name { font-size: 1.02rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .nav-cta { font-size: 10px; letter-spacing: .6px; min-height: 36px; padding-inline: 12px; }
  .hero-left { min-height: 700px; padding: 6.5rem 1.25rem 8rem; }
  .hero-title { font-size: clamp(3.1rem, 16vw, 3.75rem); line-height: 1; }
  .bismillah { font-size: clamp(4.5rem, 25vw, 7rem); top: 38%; }
  .hero-body { max-width: none; }
  .hero-actions, .cta-actions { align-items: stretch; flex-direction: column; }
  .btn { width: 100%; }
  .hero-proof { align-items: flex-start; flex-direction: column; }
  .hero-right { min-height: 440px; padding: 4rem 1.25rem 2.5rem; }
  .calligraphy-panel { min-height: 340px; }
  .hero-arabic-word { font-size: 4.2rem; }
  .right-stats { display: grid; gap: .85rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 2.3rem; }
  .right-stat strong { font-size: 2.25rem; }
  .right-stat span { font-size: 9px; letter-spacing: .9px; }
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
  .nav-logo-img { height: 28px; width: 28px; }
  .brand-name { font-size: .94rem; max-width: 142px; }
  .nav-cta { padding-inline: 10px; }
  .hero-left { padding-inline: 1rem; }
  .right-stats { grid-template-columns: 1fr; }
  .right-stat span { font-size: 10px; }
  .band-track { gap: 1.4rem; }
}

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; transition-duration: .01ms !important; }
}
</style>
</head>
<body class="loading">
<div class="preloader" id="preloader" role="status" aria-live="polite" aria-label="Loading The Muhsinat Club">
  <div class="preloader-card">
    <div class="preloader-mark ar">م</div>
    <div class="preloader-arabic ar">المحسنات</div>
    <div class="preloader-name">The Muhsinat Club</div>
    <div class="preloader-tag">Ajr Hunting for the Home in Jannah</div>
    <div class="preloader-line" aria-hidden="true"></div>
  </div>
</div>
<div class="progress" id="progress"></div>
<div class="cursor-ring" id="cursor-ring"></div>
<div class="cursor-dot" id="cursor-dot"></div>

<nav class="site-nav" id="site-nav" aria-label="Primary navigation">
  <a class="brand" href="#top" aria-label="The Muhsinat Club home">
    <!-- CHANGE 2: real brand logo replacing generated circle -->
    <img src="{{ asset('images/img1.png') }}" alt="TMC" class="nav-logo-img">
    <span class="brand-name">The Muhsinat Club</span>
  </a>
  <div class="nav-right">
    <ul class="nav-links">
      <li><a href="#features">Platform</a></li>
      <li><a href="#community">Sisterhood</a></li>
      <li><a href="#about">About</a></li>
    </ul>
    <a class="btn btn-gold nav-cta" href="#join">Join Now</a>
  </div>
</nav>

<main id="top">
  <section class="hero" aria-label="The Muhsinat Club introduction">
    <div class="hero-left pattern-gold">
      <div class="bismillah ar" aria-hidden="true">بِسْمِ اللَّهِ</div>
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
        <div class="avatars" aria-hidden="true">
          <span class="avatar">A</span>
          <span class="avatar">F</span>
          <span class="avatar">Z</span>
          <span class="avatar">K</span>
        </div>
        <p class="proof-text">Sisters across the community are already inside</p>
      </div>
    </div>
    <div class="hero-right pattern-teal">
      <div class="calligraphy-panel">
        <div class="rule" aria-hidden="true"></div>
        <div class="hero-arabic-word ar">المحسنات</div>
        <div class="rule" aria-hidden="true"></div>
        <p class="translation">The Doers of Good</p>
        <svg class="star" viewBox="0 0 100 100" aria-hidden="true">
          <path fill="currentColor" d="M50 0 61 32 93 7 68 39 100 50 68 61 93 93 61 68 50 100 39 68 7 93 32 61 0 50 32 39 7 7 39 32Z"/>
        </svg>
        <div class="right-stats" aria-label="Community highlights">
          <div class="right-stat"><strong>500+</strong><span>Sisters</span></div>
          <div class="right-stat"><strong>7</strong><span>Events monthly</span></div>
          <div class="right-stat"><strong>∞</strong><span>Ajr potential</span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="band" aria-label="Platform highlights">
    <div class="band-track" id="band-track"></div>
  </section>

  <section class="stats-bar" aria-label="The Muhsinat Club statistics">
    <div class="stat">
      <div class="stat-number" data-count="500" data-suffix="+">0</div>
      <div class="stat-label">Sisters &amp; growing</div>
    </div>
    <div class="stat">
      <div class="stat-number" data-count="12">0</div>
      <div class="stat-label">Halaqahs this year</div>
    </div>
    <div class="stat">
      <div class="stat-number" data-count="340">0</div>
      <div class="stat-label">Average coins on join</div>
    </div>
  </section>

  <!-- CHANGE 5: features section uses img4 via .features::before CSS override above -->
  <section class="section features pattern-teal" id="features">
    <div class="section-inner">
      <div class="fade-up">
        <span class="eyebrow">The Platform</span>
        <h2 class="section-heading">Everything a sister needs, in one <em>sacred space.</em></h2>
      </div>
      <div class="features-grid">
        <article class="feature-card wide">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg></div>
          <h3 class="feature-title">Halaqahs &amp; Events</h3>
          <p class="feature-body">Browse upcoming circles, reserve your seat, and keep your week anchored by gatherings designed for knowledge, remembrance, and meaningful connection.</p>
          <span class="feature-tag">RSVP enabled</span>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M12 2l2.5 7.2H22l-6 4.5 2.3 7.3L12 16.7 5.7 21 8 13.7 2 9.2h7.5z"/></svg></div>
          <h3 class="feature-title">Jannah Coins</h3>
          <p class="feature-body">Receive thoughtful rewards for showing up, inviting sisters, attending gatherings, and building habits that beautify your everyday worship.</p>
          <span class="feature-tag">Earn &amp; redeem</span>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 19.5 5 15l11-11a2.1 2.1 0 0 1 3 3L8 18z"/><path d="M13.5 5.5l5 5"/><path d="M4 20h16"/></svg></div>
          <h3 class="feature-title">Private Journal</h3>
          <p class="feature-body">A quiet space for reflections, gratitude, goals, and du'a. Private by design, gentle in tone, and always ready when your heart needs room.</p>
          <span class="feature-tag">Fully private</span>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M6 7h12l-1 14H7z"/><path d="M9 7a3 3 0 0 1 6 0"/><path d="M6 7 4 3h16l-2 4"/></svg></div>
          <h3 class="feature-title">The Souq</h3>
          <p class="feature-body">Discover Muslim women-owned brands, support values-aligned commerce, and list your own work inside a trusted member marketplace.</p>
          <span class="feature-tag">Shop with sisters</span>
        </article>
        <article class="feature-card wide">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 10h5"/><path d="M7 14h10"/><circle cx="17" cy="10" r="1.5"/></svg></div>
          <h3 class="feature-title">Legacy Card</h3>
          <p class="feature-body">Carry a digital membership card that honours your place in the sisterhood, your progress, and the small consistent acts that shape a lasting legacy.</p>
          <span class="feature-tag">Exclusive to members</span>
        </article>
        <article class="feature-card">
          <div class="feature-icon"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></div>
          <h3 class="feature-title">Resources Library</h3>
          <p class="feature-body">Access curated du'a guides, reflections, study notes, and recordings for the seasons when you need knowledge close at hand.</p>
          <span class="feature-tag">Curated content</span>
        </article>
      </div>
    </div>
  </section>

  <section class="arabic-divider" aria-label="Arabic divider">
    <div class="divider-line"></div>
    <span class="divider-arabic ar">ٱلْمُحْسِنَاتُ</span>
    <div class="divider-line bottom"></div>
    <p class="divider-caption">The Muhsinat Club</p>
  </section>

  <section class="section how pattern-gold" id="how">
    <div class="section-inner">
      <div class="fade-up">
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

  <section class="section testimonials" id="community">
    <div class="section-inner">
      <div class="fade-up">
        <span class="eyebrow">The Sisterhood</span>
        <h2 class="section-heading">Words from the <em>club.</em></h2>
      </div>
      <div class="testimonial-grid">
        <article class="testimonial">
          <div class="quote-mark">"</div>
          <p class="quote">The journal feels like a quiet room for my thoughts. I come in for a few minutes and leave with my heart softer and my intentions clearer.</p>
          <div class="author"><span class="author-initials">A</span><div><p class="author-name">Amina K.</p><p class="author-location">United Kingdom</p></div></div>
        </article>
        <article class="testimonial">
          <div class="quote-mark">"</div>
          <p class="quote">TMC makes faith feel beautifully practical. The halaqahs, reminders, and sisterly warmth helped me rebuild consistency without feeling alone.</p>
          <div class="author"><span class="author-initials">F</span><div><p class="author-name">Fatimah O.</p><p class="author-location">Nigeria</p></div></div>
        </article>
        <article class="testimonial">
          <div class="quote-mark">"</div>
          <p class="quote">The Souq introduced me to women-led brands I now genuinely love. It feels good to support sisters while staying connected to the community.</p>
          <div class="author"><span class="author-initials">Z</span><div><p class="author-name">Zainab M.</p><p class="author-location">Canada</p></div></div>
        </article>
      </div>
    </div>
  </section>

  <section class="section cta pattern-teal" id="join">
    <div class="cta-inner fade-up">
      <p class="cta-arabic ar">وَاللَّهُ يُحِبُّ الْمُحْسِنِينَ</p>
      <p class="cta-translation">"And Allah loves those who do good." — Qur'an 3:134</p>
      <p class="cta-eyebrow">Ready, sister?</p>
      <h2 class="cta-heading">Your seat in the <em>sisterhood</em> is waiting.</h2>
      <p class="cta-body">Step into a warm, refined space for worship, learning, reflection, rewards, and sincere companionship built for Muslim women.</p>
      <div class="cta-actions">
        <a class="btn btn-gold" href="#top">Create Your Account</a>
        <a class="btn btn-outline" href="#features">See the platform</a>
      </div>
    </div>
  </section>
</main>

<footer class="footer" id="about">
  <div class="footer-inner">
    <div class="footer-top">
      <div>
        <!-- CHANGE 4: img1 at 44px next to brand name (img2 has white bg, footer is dark) -->
        <div class="footer-brand-row">
          <img src="{{ asset('images/img1.png') }}" alt="TMC" style="height:44px;object-fit:contain;">
          <p class="footer-brand">The Muhsinat Club</p>
        </div>
        <p class="footer-tagline">Ajr Hunting for the Home in Jannah</p>
        <p class="footer-arabic ar">المحسنات نادي</p>
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
      <p>© <span id="year"></span> The Muhsinat Club. All rights reserved.</p>
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
  window.setTimeout(() => preloader.remove(), 850);
}

window.addEventListener('load', () => window.setTimeout(hidePreloader, 650));
window.setTimeout(hidePreloader, 2600);

function updateChrome() {
  const max = document.documentElement.scrollHeight - window.innerHeight;
  const pct = max > 0 ? (window.scrollY / max) * 100 : 0;
  nav.classList.toggle('scrolled', window.scrollY > 60);
  progress.style.width = pct + '%';
}
updateChrome();
window.addEventListener('scroll', updateChrome, { passive: true });

const bandItems = ['Daily Reflections', 'Jannah Coins', 'Private Journal', 'Member Souq', 'Halaqahs', 'Legacy Card', 'Faith Community'];
const bandTrack = document.getElementById('band-track');
const bandHtml = bandItems.map(item => `<span class="band-item">✦ ${item}</span>`).join('');
bandTrack.innerHTML = bandHtml + bandHtml;

const revealObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    entry.target.classList.add('visible');
    revealObserver.unobserve(entry.target);
  });
}, { threshold: 0.16 });

function observeWithStagger(selector, delay) {
  document.querySelectorAll(selector).forEach((el, index) => {
    if (delay) el.style.transitionDelay = `${index * delay}ms`;
    revealObserver.observe(el);
  });
}

observeWithStagger('.fade-up', 0);
observeWithStagger('.feature-card', 80);
observeWithStagger('.testimonial', 90);

const stepObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const steps = Array.from(document.querySelectorAll('.step'));
    const delay = steps.indexOf(entry.target) * 180;
    window.setTimeout(() => entry.target.classList.add('visible'), delay);
    stepObserver.unobserve(entry.target);
  });
}, { threshold: 0.22 });
document.querySelectorAll('.step').forEach(step => stepObserver.observe(step));

const statObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (!entry.isIntersecting) return;
    const el = entry.target;
    const target = Number(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    const duration = 1800;
    const start = performance.now();
    function tick(now) {
      const progressValue = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progressValue, 3);
      el.textContent = Math.floor(eased * target) + suffix;
      if (progressValue < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    statObserver.unobserve(el);
  });
}, { threshold: 0.5 });
document.querySelectorAll('[data-count]').forEach(stat => statObserver.observe(stat));

const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
if (finePointer) {
  const dot = document.getElementById('cursor-dot');
  const ring = document.getElementById('cursor-ring');
  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let ringX = mouseX;
  let ringY = mouseY;

  document.addEventListener('mousemove', event => {
    mouseX = event.clientX;
    mouseY = event.clientY;
    dot.style.left = mouseX + 'px';
    dot.style.top = mouseY + 'px';
  });

  function animateCursor() {
    ringX += (mouseX - ringX) * 0.12;
    ringY += (mouseY - ringY) * 0.12;
    ring.style.left = ringX + 'px';
    ring.style.top = ringY + 'px';
    requestAnimationFrame(animateCursor);
  }
  animateCursor();

  document.querySelectorAll('a, button').forEach(item => {
    item.addEventListener('mouseenter', () => {
      dot.style.transform = 'translate(-50%, -50%) scale(1.65)';
      ring.style.width = '48px';
      ring.style.height = '48px';
    });
    item.addEventListener('mouseleave', () => {
      dot.style.transform = 'translate(-50%, -50%) scale(1)';
      ring.style.width = '32px';
      ring.style.height = '32px';
    });
  });
}
</script>
</body>
</html>
