# The Muhsinat Club (TMC) — Claude Code Context

## What this project is
A Laravel 11 PWA for Muslim women. Faith-based community platform.
One GitHub repo: landing page (live at /) + Laravel member app (/home) + Filament admin (/admin).

## Stack
- Laravel 11, PHP 8.2+, Blade, Livewire 3, Alpine.js, Tailwind CSS v3
- MySQL 8, Filament v3, Spatie Laravel Permission, Laravel Fortify
- minishlink/web-push for push notifications
- Queue driver: database (dev), Redis (prod)

## Brand (never change these)
- Teal: #1A6B72 | Gold: #C8A84B | Plum: #3D1A47 | Ivory: #FAF8F3 | Ink: #1C1A17
- Dancing Script → all headings | Nunito → all UI/body | Amiri → Arabic text only
- img1.png = logo mark | img2.png = full logo | img3.png = Arabic logo | img4.png = pattern

## Non-negotiable rules
1. `GET /` must return the landing page, unchanged, at all times
2. Journal `body` field has `encrypted` cast — admin can never read content
3. All permissions enforced server-side via Laravel Policies, never UI-only
4. All admin actions logged via `AuditLogService::log()`
5. `php artisan test` must pass before ending any session
6. `git commit` at the end of every phase

## Where to find specs
- Full feature requirements    → docs/PRD.md
- Database schema + routes     → docs/TRD.md
- CSS tokens + components      → docs/DESIGN_SYSTEM.md
- Screen-by-screen UI specs    → docs/DESIGN_GUIDE.md
- Phase prompts (what to build) → docs/BUILD_PHASES.md

## Current phase
<!-- Update this line as you progress -->
Phase: 4 — Resources Library & Private Journal
Status: Complete ✓

## Key services (already exist after Phase 0)
- `App\Services\AuditLogService::log(action, model, old, new)`
- `App\Services\CoinsService` (getBalance, award, deduct, getHistory)
- `App\Services\RsvpService` (rsvp, cancel, isRsvpd)
- `App\Services\DuaListService` (save, saveManual, remove, isSaved)
- `App\Services\PushNotificationService` (send, sendToMany)

## Middleware chain for member routes
`auth` → `EnsureOnboardingComplete`
Exception: `/onboarding` only needs `auth`

## Before writing any code, read
The relevant section in docs/TRD.md for the schema.
The relevant screen spec in docs/DESIGN_GUIDE.md for the UI.
The current phase prompt in docs/BUILD_PHASES.md.
