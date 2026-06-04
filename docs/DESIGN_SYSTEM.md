# The Muhsinat Club (TMC) — Design System
**Version:** 1.0

---

## 1. Brand Identity

TMC is a faith-based community platform for Muslim women. The visual language is **refined Islamic luxury editorial** — think warm, feminine, premium, and spiritually grounded. Not clinical. Not generic. Not trendy. Every design decision should feel intentional and belonging to this community.

**Brand assets (in `/public/images/`):**
| File | Usage |
|------|-------|
| `img1.png` | Logo mark only (emblem, no text) — nav, favicon, legacy card |
| `img2.png` | Full logo (emblem + wordmark + tagline) — footer, onboarding |
| `img3.png` | Arabic calligraphy alternate logo — section divider, decorative |
| `img4.png` | Botanical scatter pattern — section backgrounds |

---

## 2. Colour Tokens

### CSS Variables (define in `:root`)
```css
:root {
  /* Primary */
  --teal:        #1A6B72;   /* main brand — nav active, buttons, headings */
  --teal-dk:     #0D3F44;   /* hero bg, dark sections, How It Works */
  --teal-md:     #2A8A93;   /* accents, hover states */
  --teal-lt:     #D6EDEF;   /* card hover, light backgrounds */

  /* Accent */
  --gold:        #C8A84B;   /* CTAs, tags, rules, band, coins */
  --gold-lt:     #E8CB7A;   /* lighter gold — hero text, hover */
  --gold-pale:   #FDF6E3;   /* CTA section background */

  /* Plum (official brand colour) */
  --plum:        #3D1A47;   /* How It Works section bg, avatar bg */
  --plum-md:     #5A2E6B;   /* hover, accents */
  --plum-lt:     #F3EAF7;   /* light plum for subtle uses */

  /* Supporting palette */
  --taupe:       #9E8877;   /* supporting neutral */
  --mint:        #A8E4CF;   /* fresh accent */
  --beige:       #EAD9A8;   /* warm card backgrounds */
  --beige-lt:    #FAF5E8;   /* very light beige */

  /* Neutrals */
  --ivory:       #FAF8F3;   /* page background */
  --white:       #FFFFFF;
  --ink:         #1C1A17;   /* primary text */
  --ink-md:      #3D3A35;   /* secondary text */
  --ink-soft:    #6B6760;   /* muted text, captions */

  /* Functional */
  --border:      #E2E8F0;   /* default border colour */
  --shadow-sm:   0 2px 8px rgba(0,0,0,0.06);
  --shadow-md:   0 8px 24px rgba(0,0,0,0.08);
  --shadow-lg:   0 20px 50px rgba(26,107,114,0.1);
}
```

### Tailwind Config Extension
```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        teal: {
          DEFAULT: '#1A6B72',
          dk: '#0D3F44',
          md: '#2A8A93',
          lt: '#D6EDEF',
        },
        gold: {
          DEFAULT: '#C8A84B',
          lt: '#E8CB7A',
          pale: '#FDF6E3',
        },
        plum: {
          DEFAULT: '#3D1A47',
          md: '#5A2E6B',
          lt: '#F3EAF7',
        },
        ivory: '#FAF8F3',
        ink: {
          DEFAULT: '#1C1A17',
          md: '#3D3A35',
          soft: '#6B6760',
        },
      },
    },
  },
}
```

---

## 3. Typography

### Font Stack
```css
:root {
  --font-display: 'Dancing Script', cursive;
  --font-body:    'Nunito', sans-serif;
  --font-arabic:  'Amiri', serif;
}
```

### Google Fonts Import
```html
<link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500;600;700&family=Nunito:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Amiri:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
```

### Type Scale

| Token | Font | Size | Weight | Use |
|-------|------|------|--------|-----|
| `display-hero` | Dancing Script | clamp(3.2rem, 5vw, 5.5rem) | 400 | Hero H1 |
| `display-h2` | Dancing Script | clamp(2.4rem, 3.5vw, 4rem) | 400 | Section headings |
| `display-h3` | Dancing Script | 1.55rem | 500 | Card titles |
| `display-sm` | Dancing Script | 1.1rem | 400 | Taglines, eyebrows, quotes |
| `body-lg` | Nunito | 0.95rem | 300 | Section body copy |
| `body-md` | Nunito | 0.875rem | 300 | Card body, descriptions |
| `body-sm` | Nunito | 12.5px | 400 | Captions, metadata |
| `label` | Nunito | 11px | 500 | Section labels (uppercase) |
| `button` | Nunito | 12.5px | 600 | Button text (uppercase) |
| `nav` | Nunito | 12.5px | 400 | Nav links (uppercase) |
| `arabic-lg` | Amiri | 5rem+ | 700 | Decorative Arabic display |
| `arabic-md` | Amiri | 1.4rem | 400 | Qur'anic verses, captions |
| `arabic-sm` | Amiri | 1rem | 400 | Footer, labels |

### Typography Rules
- **Never** use Dancing Script for body copy
- **Never** use Nunito or Dancing Script to render Arabic text
- **Always** apply `direction: rtl; unicode-bidi: embed;` to Arabic elements
- **Never** add `font-style: italic` to Dancing Script — its natural style is the aesthetic
- Line height: body copy `1.8`, headings `1.1–1.15`
- Letter spacing: labels and buttons `0.8px–2px`

---

## 4. Spacing System

Use Tailwind's default spacing scale. Key values:

| Token | Value | Common use |
|-------|-------|-----------|
| Section padding Y | `8rem` (`py-32`) | Desktop sections |
| Section padding Y mobile | `5rem` (`py-20`) | Mobile sections |
| Container padding X | `5rem` (`px-20`) | Desktop |
| Container padding X mobile | `1.5rem` (`px-6`) | Mobile |
| Container max-width | `1200px` | All main content |
| Card padding | `2.5rem` (`p-10`) | Feature cards |
| Card padding mobile | `1.5rem` (`p-6`) | Mobile cards |

---

## 5. Components

### Buttons

```css
/* Base */
.btn {
  font-family: var(--font-body);
  font-size: 12.5px;
  font-weight: 600;
  letter-spacing: 0.8px;
  text-transform: uppercase;
  padding: 13px 30px;
  border-radius: 2px;  /* Never use rounded-full on buttons */
  border: none;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

/* Shimmer on hover */
.btn::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transform: translateX(-100%);
  transition: transform 0.5s;
}
.btn:hover::after { transform: translateX(100%); }
.btn:hover { transform: translateY(-2px); }

/* Variants */
.btn-gold      { background: var(--gold); color: var(--teal-dk); }
.btn-teal      { background: var(--teal); color: white; }
.btn-teal-ol   { background: transparent; color: var(--teal); border: 1px solid var(--teal); }
.btn-ghost     { background: transparent; color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.3); }
```

**Rules:**
- Max `border-radius: 3px` on buttons — never pill/capsule
- Always uppercase Nunito 600
- Gold button = primary action
- Teal button = secondary action
- Ghost button = used on dark backgrounds only

---

### Labels / Section Tags

```html
<span class="label">The Platform</span>
```
```css
.label {
  font-family: var(--font-body);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 2.2px;
  text-transform: uppercase;
  color: var(--gold);
}
```

---

### Feature Cards

```css
.fc {
  background: var(--ivory);
  padding: 2.5rem;
  position: relative;
  overflow: hidden;
  transition: background 0.35s;
}

/* Left border reveal on hover */
.fc::before {
  content: '';
  position: absolute;
  left: 0; bottom: 0;
  width: 3px; height: 0;
  background: var(--gold);
  transition: height 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.fc:hover::before { height: 100%; }
.fc:hover { background: var(--teal-lt); }

/* Wide variant (grid col span 2) */
.fc-wide { grid-column: span 2; }
/* Wide cards use beige bg */
.fc-wide { background: var(--beige-lt); }
.fc-wide:hover { background: #DFD09A; }
```

---

### Nav

```css
/* Default (transparent, on hero) */
nav {
  position: fixed;
  padding: 1.4rem 5rem;
  transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
nav .nav-link { color: rgba(255,255,255,0.75); }

/* Scrolled */
nav.scrolled {
  background: rgba(250,248,243,0.96);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(200,168,75,0.2);
  padding: 1rem 5rem;
}
nav.scrolled .nav-link { color: var(--ink-md); }

/* Underline animation on hover */
.nav-link::after {
  content: '';
  position: absolute;
  bottom: -3px; left: 0;
  width: 0; height: 1px;
  background: var(--gold);
  transition: width 0.35s;
}
.nav-link:hover::after { width: 100%; }
```

---

### Bottom Navigation (Member App)

```html
<!-- Fixed bottom bar, 7 tabs -->
<nav class="bottom-nav">
  <a href="/home" class="bn-tab active">
    <svg><!-- home icon --></svg>
    <span>Home</span>
  </a>
  <!-- Events · Souq · Community · Wallet · Journal · Profile -->
</nav>
```
```css
.bottom-nav {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  height: 64px;
  background: white;
  border-top: 1px solid var(--border);
  display: flex;
  z-index: 50;
}
.bn-tab {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 400;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--ink-soft);
  text-decoration: none;
  gap: 3px;
}
.bn-tab.active { color: var(--teal); }
.bn-tab svg { width: 22px; height: 22px; }
```

---

### Avatar / Initials Circle

```css
.avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-display);
  font-size: 14px;
  color: white;
  flex-shrink: 0;
}
/* Colour variants by letter range */
.avatar-teal  { background: var(--teal); }
.avatar-green { background: #5A7A6B; }
.avatar-plum  { background: var(--plum-md); }
.avatar-gold  { background: #8A7A3A; }
```

---

### Coins Badge

```html
<div class="coins-badge">
  <span class="coins-icon">✦</span>
  <span class="coins-value">340</span>
  <span class="coins-label">Jannah Coins</span>
</div>
```
```css
.coins-badge {
  background: rgba(200,168,75,0.1);
  border: 1px solid rgba(200,168,75,0.25);
  border-radius: 10px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.coins-icon {
  width: 24px; height: 24px;
  background: var(--gold);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
}
.coins-value {
  font-family: var(--font-display);
  font-size: 1.4rem;
  color: var(--gold-lt);
  line-height: 1;
}
```

---

## 6. Patterns & Textures

### img4 — Botanical Pattern Background

Apply to ivory/white sections via `::before` pseudo-element:

```css
.section-with-pattern {
  position: relative;
}
.section-with-pattern::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: url('/images/img4.png');
  background-size: 500px auto;
  background-repeat: repeat;
  opacity: 0.4;
  pointer-events: none;
  z-index: 0;
  animation: patternDrift 30s linear infinite;
}
.section-with-pattern > * {
  position: relative;
  z-index: 1;
}

@keyframes patternDrift {
  0%   { background-position: 0 0; }
  100% { background-position: 300px 300px; }
}
```

**Rules:**
- Apply to: Features section, CTA section (ivory/gold-pale backgrounds)
- Do NOT apply to: dark sections (teal-dk, plum)
- Opacity: 0.4 on ivory, 0.35 on gold-pale
- On dark hero: use the pattern at opacity 0.1 with gold-tinted SVG variant

### Arabic Divider Pattern

```html
<div class="arabic-divider">
  <div class="ad-rule"></div>
  <img src="/images/img3.png" alt="The Muhsinat Club" class="ad-calligraphy">
  <div class="ad-rule"></div>
</div>
```
```css
.arabic-divider {
  padding: 3rem 0;
  background: white;
  text-align: center;
  border-top: 1px solid rgba(200,168,75,0.1);
  border-bottom: 1px solid rgba(200,168,75,0.1);
}
.ad-rule {
  width: 60px; height: 1px;
  background: var(--gold);
  opacity: 0.5;
  margin: 0 auto 1.2rem;
}
.ad-calligraphy {
  max-width: 400px;
  margin: 0 auto;
}
```

---

## 7. Arabic Text Rules

```css
/* Apply to ALL Arabic text elements */
[lang="ar"], .arabic {
  font-family: 'Amiri', serif;
  direction: rtl;
  unicode-bidi: embed;
}

/* Decorative large Arabic (hero watermark, section display) */
.arabic-display {
  font-family: 'Amiri', serif;
  font-weight: 700;
  direction: rtl;
  unicode-bidi: embed;
}

/* Qur'anic verse */
.quran-verse {
  font-family: 'Amiri', serif;
  font-size: 1.4rem;
  color: var(--teal-md);
  direction: rtl;
  unicode-bidi: embed;
  line-height: 1.8;
  text-align: center;
}
```

**Critical rules:**
- Never render Arabic in Dancing Script or Nunito
- Never mix Arabic inline within English text flow
- Always wrap Arabic in an element with `lang="ar"`
- Amiri is the only permitted font for Arabic

---

## 8. Animation Tokens

```css
@keyframes slideUp {
  from { opacity: 0; transform: translateY(22px); }
  to   { opacity: 1; transform: none; }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}

@keyframes orbPulse {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.07); }
}

@keyframes bandScroll {
  0%   { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

@keyframes shimmer {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

@keyframes lineDraw {
  to { width: 55%; }
}

@keyframes patternDrift {
  0%   { background-position: 0 0; }
  100% { background-position: 300px 300px; }
}
```

**Scroll-triggered animations (via IntersectionObserver):**
```css
.fu {
  opacity: 0;
  transform: translateY(26px);
  transition: opacity 0.7s ease, transform 0.7s ease;
}
.fu.visible {
  opacity: 1;
  transform: none;
}
```

---

## 9. Legacy Card Specification

The Legacy Card is a full-screen digital membership card. It must match these exact specifications:

```
Background: var(--teal-dk) with img4.png pattern at 8% opacity
Border: 1px solid rgba(200,168,75,0.3)
Border-radius: 12px (card), 0 (hero sections)

Content layout (centred, top to bottom):
1. img1.png logo mark — 48px
2. Gold horizontal rule — 40px wide, 1px
3. Arabic text: المحسنات — Amiri 700, 3rem, var(--gold-lt), rtl
4. Gold horizontal rule — 40px wide, 1px
5. Member display name — Dancing Script 1.8rem, white
6. Membership tier — Nunito 11px uppercase, rgba(255,255,255,0.5)
7. Member since — Nunito 300, rgba(255,255,255,0.4)
8. Coins: ✦ icon + balance — Dancing Script 1.4rem, var(--gold-lt)
```

---

## 10. Responsive Breakpoints

```css
/* Single responsive breakpoint */
@media (max-width: 960px) {
  :root {
    --section-px: 1.5rem;  /* replaces 5rem */
  }

  /* Nav: hide links */
  .nav-links { display: none; }

  /* Hero: single column */
  .hero-inner { grid-template-columns: 1fr; }
  .hero-r { display: none; }

  /* Grids: collapse */
  .features-grid,
  .steps-grid,
  .testimonials-grid { grid-template-columns: 1fr; }

  /* Wide cards: reset span */
  .fc-wide { grid-column: span 1; }

  /* Footer: 2-col then 1-col */
  .footer-top { grid-template-columns: 1fr 1fr; gap: 2rem; }

  /* Bottom nav: ensure visible */
  .bottom-nav { display: flex; }
}
```

---

## 11. Do's and Don'ts

| ✅ Do | ❌ Don't |
|-------|---------|
| Use Dancing Script for all display headings | Use Dancing Script for body copy |
| Use Nunito for all UI text | Use Inter, Roboto, or system fonts |
| Use Amiri for all Arabic text | Render Arabic in any other font |
| Use `border-radius: 2–3px` on buttons | Use rounded/pill buttons |
| Use teal-dk (#0D3F44) for How It Works bg | Use plum for How It Works bg |
| Apply img4 pattern on ivory sections | Apply img4 pattern on dark sections |
| Render img3 on white/ivory backgrounds only | Render img3 on teal or plum backgrounds |
| Use plum as a section background (How It Works = use teal-dk) | Use plum for main backgrounds |
| Use var(--gold) for icons, rules, accents | Use gold as a large background fill |
| Scale avatars with Dancing Script initials | Use generic avatar placeholders |
