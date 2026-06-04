# TMC Repo Notes For OpenCode

## Current State
- This is already a Laravel 11 app. The previous pre-Laravel setup notes are stale.
- Current app surface is still very small: `routes/web.php` only defines `GET /` -> `resources/views/landing.blade.php` and `GET /offline` -> `resources/views/offline.blade.php`.
- Filament is installed and configured at `/admin` in `app/Providers/Filament/AdminPanelProvider.php`, but there are no app-specific Filament resources/pages yet.
- `CLAUDE.md` still tracks the intended phased build and non-negotiables. Use it as product context, not as proof of implementation.

## Commands
- Install deps: `composer install && npm install`
- Start the full local stack: `composer dev`
- Frontend only: `npm run dev`
- Production assets: `npm run build`
- Default verification: `php artisan test`
- When you need seeded roles/admin locally: `php artisan migrate --seed`

## Test And Env Gotchas
- `.env.example` is MySQL-first: `DB_CONNECTION=mysql`, `DB_DATABASE=tmc_app`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`.
- `phpunit.xml` does not switch tests to sqlite or an in-memory DB. Unless you override env vars yourself, tests use the app's configured database.
- `phpunit.xml` does force `QUEUE_CONNECTION=sync` and `SESSION_DRIVER=array` during tests.
- The current test suite is still the Laravel starter tests in `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php`; there is no `RefreshDatabase` coverage yet.

## Repo-Specific Wiring
- Keep `GET /` serving the existing landing page. The landing view uses hard-coded brand styling and `asset('images/img*.png')` references from `public/images/`.
- Filament branding also depends on `public/images/img1.png` and `public/images/img2.png` via `AdminPanelProvider`.
- Roles are seeded by `database/seeders/RoleSeeder.php`: `super_admin`, `admin`, `moderator`, `content_editor`, `volunteer`, `member`.
- `database/seeders/AdminUserSeeder.php` creates `admin@themuhsinatclub.com` with password `Change1234!` and assigns `super_admin`.
- `app/Models/User.php` gates Filament access by role through `canAccessPanel()`.
- `app/Services/AuditLogService.php` already exists and inserts directly into `audit_logs`.
- Composer `post-autoload-dump` runs `scripts/patch-laravel-framework-config.php`, which patches a Laravel vendor database config constant check for MySQL SSL compatibility. Do not remove or bypass it casually.

## Phase Work Rules
- Before implementing a phase, read `docs/BUILD_PHASES.md`, then the matching schema/details in `docs/TRD.md`, then the UI spec in `docs/DESIGN_GUIDE.md`.
- Preserve these constraints from `CLAUDE.md` and the docs: landing page at `GET /` stays intact, journal bodies must remain encrypted, admins must be unable to read journal content, permission checks must be server-side policies, and every admin mutation must call `AuditLogService::log()`.
- End implementation sessions with `php artisan test`. For phase-completion checks, also run `php artisan route:list` and `php artisan migrate:status`.

## OpenCode Helpers
- `/user:phase <n>` reads `docs/BUILD_PHASES.md`, `docs/TRD.md`, `docs/DESIGN_GUIDE.md`, and `AGENTS.md`.
- `/user:check` is intended to run the standard Laravel verification commands after the app exists.
