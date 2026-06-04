# The Muhsinat Club (TMC) — Design Guide
**For:** Developers and AI coding tools  
**Purpose:** How to apply the TMC brand correctly across every screen

---

## 1. The Brand in One Sentence

TMC is a warm, faith-centred community for Muslim women. Every screen should feel like a calm, beautiful, private space — never noisy, never generic, never cold.

---

## 2. Brand Assets Quick Reference

| File | What it is | Where to use it |
|------|-----------|----------------|
| `img1.png` | Logo mark (emblem only, no text) | Nav, favicon, Legacy Card, PWA icons |
| `img2.png` | Full logo (emblem + "The Muhsinat Club" + tagline) | Footer brand column, onboarding welcome |
| `img3.png` | Arabic calligraphy alternate logo | Section divider between Features and How it Works |
| `img4.png` | Botanical scatter pattern | Background texture on ivory/cream sections |

**Critical rules:**
- `img1` and `img2` have white backgrounds — do not place on dark/teal backgrounds
- `img3` must only appear on white or ivory backgrounds
- `img4` is a repeating background texture applied at low opacity via `::before` — never as a full-bleed image

---

## 3. Section Backgrounds — When to Use What

| Section | Background | Pattern |
|---------|-----------|---------|
| Landing hero | `#0A2E34 → #1A6B72` gradient | img4 at opacity 0.1 (gold-tinted SVG variant) |
| Stats bar | `#0D3F44` | None |
| Gold band | `#C8A84B` | None (shimmer animation only) |
| Features | `#FAF8F3` (ivory) | img4 at opacity 0.4 via `::before` |
| Arabic divider | `#FFFFFF` | None |
| How it Works | `#0D3F44` (deep teal) | Botanical gold strokes SVG at opacity 0.09 |
| Testimonials | `#FFFFFF` | None |
| CTA | `#FDF6E3` (gold-pale) | img4 at opacity 0.35 via `::before` |
| Footer | `#1C1A17` (ink) | None |

**Rule:** Never use plum (`#3D1A47`) as a section background. It is used only for avatar backgrounds and small accents.

---

## 4. Screen-by-Screen Design Guide

### Authentication Screens (`/register`, `/login`)

```
Layout: Single column, centred, max-width 420px, full-height ivory bg
Header: img1 logo mark (36px) + "The Muhsinat Club" in Dancing Script
Form card: white bg, subtle shadow, 2rem padding, border-radius 4px
Heading: Dancing Script 2rem, var(--teal-dk)
Labels: Nunito 13px, var(--ink-md)
Inputs: full-width, 12px border-radius, border var(--border)
  focus: border var(--teal), ring shadow rgba(26,107,114,0.15)
Submit button: .btn-gold, full-width
Links: Nunito 13px, var(--teal), no underline
Error messages: Nunito 12px, red, below the relevant input
```

---

### Onboarding Wizard (`/onboarding`)

```
Layout: Full-screen, ivory bg, max-width 520px centred
Progress bar: thin gold line at top, fills as steps progress
Step indicator: "Step X of 4" Nunito 11px uppercase gold
Heading: Dancing Script 2.5rem var(--teal-dk) per step

Step 1 — Interests:
  Interest chips: pill buttons (rounded-full for chips only, not CTAs)
  Selected: teal bg white text | Unselected: ivory bg ink-md text border border
  Min 1, max 5 — show count "X/5 selected"

Step 2 — Goals:
  4 cards in a 2x2 grid
  Each: icon (Heroicon) + title + 1-line description
  Selected: teal-lt bg, teal border | Unselected: white bg, border border

Step 3 — Notification prefs:
  Toggle switches (Alpine.js)
  Teal when on, gray when off
  Label left, toggle right

Step 4 — Welcome:
  Celebratory full-screen
  img1 logo 60px, centred
  Dancing Script 2.5rem: "Welcome to The Muhsinat Club"
  Gold coin animation or static: "You've earned 50 Jannah Coins"
  Dancing Script italic 1.1rem, gold
  CTA button: .btn-gold "Enter the Club →"
```

---

### Home Dashboard (`/home`)

```
Layout: Single column, max-width 480px centred, bottom nav present
Top bar: img1 logo (28px) left + bell icon right (notification count badge)
Bottom padding: 80px (clears bottom nav)

Greeting card:
  Background: var(--teal) with img4 overlay at 8%
  "Assalamu Alaykum, [name]" — Dancing Script 1.8rem white
  Islamic phrase — Dancing Script italic 1rem rgba(255,255,255,0.55)
  Border-radius: 12px, padding 1.2rem

Announcement banner (if exists):
  Background: var(--gold)
  Title: Nunito 500 var(--teal-dk)
  Preview text: Nunito 300 var(--teal-dk) opacity 0.8
  Tap arrow → full announcement

Coins card:
  Background: rgba(200,168,75,0.08)
  Border: 1px solid rgba(200,168,75,0.22)
  ✦ icon + balance in Dancing Script 1.4rem var(--gold)
  Border-radius: 10px

Events preview:
  Label: "UPCOMING HALAQAHS" Nunito 11px uppercase var(--ink-soft)
  Event cards: white bg, border var(--border), radius 8px
  Title: Nunito 500 var(--ink), date: Nunito 300 var(--ink-soft)
  RSVP button: .btn-teal small

Quick actions:
  4 icon + label tiles in a row
  Background: white, border var(--border), radius 8px
  Icon: 22px Heroicon, var(--teal)
  Label: Nunito 10px uppercase var(--ink-soft)
```

---

### Events (`/events`, `/events/{slug}`)

```
Events list:
  Tab bar: Upcoming | Past | My RSVPs
  Active tab: var(--teal) border-bottom 2px
  Event card: white bg, optional cover image (16:9 ratio, object-cover)
  Title: Nunito 500 var(--ink)
  Date: Nunito 300 var(--ink-soft), formatted "Mon 12 Jan 2025, 7pm"
  Location badge: pill — Online (teal-lt text teal) | In Person (gold-pale text gold)
  RSVP button: .btn-teal small | "You're going ✓" state: disabled, teal-lt bg

Event detail:
  Cover image full-width header (if present)
  Title: Dancing Script 2rem var(--teal-dk)
  Meta row: date + location badge + RSVP count
  Description: Nunito 300 line-height 1.8
  External link button: .btn-teal-ol
  RSVP button: .btn-gold (or confirmed state)
```

---

### Resources Library (`/resources`, `/resources/{slug}`)

```
Library:
  Category tabs: horizontal scroll on mobile
  Active: var(--teal) bg white text | Inactive: ivory bg ink-soft text
  Search: full-width, Nunito 14px
  Resource card: white bg, border var(--border)
  Category badge: pill, teal-lt text-teal
  Type icon: 18px SVG var(--ink-soft)
  Title: Nunito 500 var(--ink)
  Description: Nunito 300 12px var(--ink-soft) 2-line clamp

Detail - Du'a type:
  Arabic text: Amiri 1.4rem var(--ink), direction rtl, line-height 2
  Translation: Nunito 300 0.9rem var(--ink-soft) italic
  "Save to My Du'a List" button: .btn-teal-ol
  Saved state: "Saved ✓" disabled teal
```

---

### Journal (`/journal`)

```
Two-tab screen: Entries | Du'a List
Tab bar: same style as Events

New entry modal/slide-over:
  Title: Dancing Script 1.5rem "New Entry"
  Date: date input, default today
  Mood row: 6 emoji buttons, 40px each
    Selected: teal border 2px, teal-lt bg | Unselected: border border
  Body textarea: full-width, min-height 160px, Nunito 300 1rem
  Save button: .btn-teal

Entry list item:
  Mood emoji (large) + date + first 80 chars of body
  Nunito 300 var(--ink-soft) for body preview
  Edit pencil + delete trash icons (var(--ink-soft), show on hover)

Du'a List item:
  Du'a text: Amiri 1rem var(--ink), direction rtl
  Label badge: teal-lt pill if labelled
  "From Library" badge: gold-pale pill for resource-saved items
```

---

### Souq (`/souq`, `/souq/{slug}`, `/souq/apply`)

```
Listing grid: 2 col mobile, 3 col desktop, gap 1rem
Listing card:
  Logo: 60px circle, object-contain, border border, radius 50%
  Initials fallback: var(--teal) bg, Dancing Script initials white
  Business name: Nunito 500 var(--ink)
  Category badge: small pill, taupe bg var(--ink-soft) text
  Description: Nunito 300 12px var(--ink-soft) 2-line clamp

Apply form:
  Description counter: "X / 300 characters" Nunito 11px right-aligned
  Logo upload: dashed border area with upload icon

Detail page:
  Contact section: email / phone / website / Instagram as icon + link rows
  Icon: 18px Heroicon var(--teal)
```

---

### Wallet (`/wallet`)

```
Balance hero:
  Large Dancing Script var(--gold-lt) — the number, animated count-up
  "Jannah Coins" label Nunito 11px uppercase rgba(white, 0.4) (on dark bg)
  OR on ivory: Nunito 11px uppercase var(--ink-soft)

Referral section:
  Referral link box: monospace-style, ivory bg border, full-width
  Copy button: .btn-teal-ol small, "Copy" → "Copied!" with animation
  Referral count: Nunito 300 var(--ink-soft)

Catalog placeholder:
  3 greyed-out cards with lock icons
  Nunito 300 var(--ink-soft) italic "Coming soon"

History table:
  Collapsible with chevron toggle
  Date | Reason | +/- Amount
  Amount: green for earned, red for deducted
```

---

### Profile & Legacy Card (`/profile`, `/profile/legacy-card`)

```
Profile screen:
  Avatar: 80px circle, img or Dancing Script initials
  Name: Dancing Script 2rem var(--teal-dk)
  Role badge: pill, teal bg white Nunito 11px
  Stats row: 3 cards — Member since | Coins | Badges
  Interests: small teal-lt pills, Dancing Script 0.9rem
  Badges: horizontal scroll, 40px icons with label below

Legacy Card (full-screen):
  Background: var(--teal-dk) + img4 at 8% opacity
  Layout: centred, max-width 340px, margin auto
  img1 logo: 48px, centred, margin-bottom 1.2rem
  Gold rule: 40px wide, 1px, margin 0 auto 1rem
  Arabic المحسنات: Amiri 700 3rem var(--gold-lt) rtl centred
  Gold rule: same, margin 1rem auto 1.5rem
  Member name: Dancing Script 2rem white centred
  Tier: Nunito 11px uppercase rgba(255,255,255,0.5)
  Member since: Nunito 300 12px rgba(255,255,255,0.4)
  Coins: ✦ + balance Dancing Script 1.4rem var(--gold-lt)
  "Share Card" button: .btn-ghost at bottom
```

---

### Community (`/community`, `/community/spaces/{slug}`)

```
Spaces grid: same pattern as Souq grid
Space card: cover image (if set, 16:9) | name | short description

Space detail:
  Guidelines card: ivory bg, border-left 3px var(--gold)
  Guidelines heading: Nunito 600 var(--ink) "Community Guidelines"
  Body: Nunito 300 var(--ink-soft)

  Related events: horizontal scroll on mobile, 2-col desktop
  Related resources: same

Support TMC cards (3):
  "Volunteer with Us" — teal bg white text
  "Mentorship Programme" — gold-pale bg teal text
  "Donate" — ivory bg border
  Each: Dancing Script 1.4rem, short description Nunito 300

Donate page:
  Bank details in a bordered card
  Arabic بارك الله فيكم as decorative Amiri text, centred, gold 50% opacity
```

---

### Admin Dashboard (Filament)

```
Brand: Filament configured with:
  - Primary color: #1A6B72 (teal)
  - Brand logo: img2.png
  - Favicon: img1.png

Keep Filament's default layout — do not customise beyond brand colors and logo.
Custom widgets use the same spacing and font conventions as the member app.
```

---

## 5. Livewire Component Conventions

Every Livewire component must follow these UX patterns:

```html
<!-- Loading state on every action button -->
<button wire:click="submit" wire:loading.attr="disabled">
  <span wire:loading.remove>Save</span>
  <span wire:loading>Saving...</span>
</button>

<!-- Flash messages -->
<!-- Use session flash, shown in the layout, auto-dismiss after 3s -->

<!-- Empty states — always provide one, never show a blank area -->
<div class="empty-state">
  <p class="text-ink-soft">No entries yet — start writing.</p>
</div>
```

---

## 6. Member App Layout Shell

Every member screen must extend `layouts.app`:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ config('app.name') }}</title>
  <link rel="icon" href="{{ asset('images/img1.png') }}">
  <link rel="manifest" href="/manifest.json">
  <link href="Google Fonts URL" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="bg-ivory pb-20"> {{-- pb-20 clears bottom nav --}}

  {{-- Top bar --}}
  <header class="top-bar">
    <img src="{{ asset('images/img1.png') }}" alt="TMC" class="h-7">
    <livewire:notifications.bell />
  </header>

  {{-- Page content --}}
  {{ $slot }}

  {{-- Bottom navigation --}}
  <livewire:layout.bottom-nav />

  @livewireScripts
  <script src="/sw-register.js"></script>
</body>
</html>
```

---

## 7. Writing Tone for UI Copy

**Voice:** Warm, personal, faith-grounded. Like a knowledgeable older sister.

| Situation | ✅ Write | ❌ Not |
|-----------|---------|--------|
| Button CTA | "Join the Club" | "Sign Up" |
| RSVP confirmed | "You're going ✓ — insha'Allah we'll see you there" | "RSVP confirmed" |
| Empty journal | "Your journal is waiting — write your first reflection" | "No entries found" |
| Error | "Something didn't work — please try again" | "Error 422" |
| Souq approved | "Your business is now live on the Souq, alhamdulillah!" | "Listing approved" |
| Coins earned | "You've earned 50 Jannah Coins — welcome!" | "50 coins added" |

**General rules:**
- Use "sister" not "user"
- Use Islamic phrases naturally (insha'Allah, alhamdulillah, JazakAllahu Khairan) where fitting
- Never use exclamation marks excessively — one per screen maximum
- Avoid corporate/SaaS language ("leverage", "onboard", "synergy")
