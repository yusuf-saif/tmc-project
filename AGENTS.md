# TMC Repo Notes For OpenCode

## Current App Surface
- This repo is a Laravel 11 app with Fortify auth, Livewire member screens, and a Filament admin panel.
- Public routes in `routes/web.php`: `GET /` -> `resources/views/landing.blade.php`, `GET /offline` -> `resources/views/offline.blade.php`.
- Member flow is already wired: `/onboarding` uses `auth` + `verified`; `/home` adds the `onboarded` middleware alias from `bootstrap/app.php`.
- Filament is mounted at `/admin` in `app/Providers/Filament/AdminPanelProvider.php`.

## Commands
- Install deps: `composer install && npm install`
- Run the full local stack: `composer dev`
- Frontend dev server only: `npm run dev`
- Build frontend assets: `npm run build`
- Run all tests: `php artisan test`
- Run the main feature flow only: `php artisan test --filter AuthOnboardingTest`
- Seed local roles, admin, interests, and goals: `php artisan migrate --seed`

## Auth And Routing Quirks
- Fortify is installed and uses custom Blade views from `resources/views/auth/*`; do not assume starter Laravel auth UI.
- `app/Providers/FortifyServiceProvider.php` overrides Fortify responses: register redirects to `verification.notice`, email verification redirects to `/onboarding?verified=1`, and login redirects to `/home` or `/onboarding` depending on profile completion.
- Fortify features currently enabled in `config/fortify.php`: registration, password reset, email verification. Two-factor/passkey migrations exist, but those features are not enabled.
- Referral coins are awarded on the `Verified` event via `app/Listeners/AwardReferralCoins.php`, registered in `app/Providers/AppServiceProvider.php`.

## Frontend And Brand Constraints
- Keep `GET /` serving the existing landing page. The landing page is a large inline-styled Blade file, not a Vite/Tailwind screen, so avoid “cleaning it up” casually.
- Brand tokens live in both `resources/views/landing.blade.php` and `resources/css/app.css`; auth/member UI should match those tokens, not default Laravel or Filament styling.
- Brand image usage is hard-coded from `public/images/`: landing uses `img1`-`img4`, and Filament branding depends on `img1.png` and `img2.png`.
- If you are implementing a phase from the product docs, read `docs/BUILD_PHASES.md`, then the relevant schema in `docs/TRD.md`, then the UI spec in `docs/DESIGN_GUIDE.md`.

## Data And Permissions
- Roles are seeded by `database/seeders/RoleSeeder.php`: `super_admin`, `admin`, `moderator`, `content_editor`, `volunteer`, `member`.
- `database/seeders/AdminUserSeeder.php` creates `admin@themuhsinatclub.com` with password `Change1234!` and assigns `super_admin`.
- `app/Models/User.php` gates Filament access through `canAccessPanel()` and only allows `super_admin`, `admin`, `moderator`, and `content_editor`.
- Preserve repo-level product constraints from the docs and `CLAUDE.md`: landing page at `/` must remain intact, journal bodies must stay encrypted, permissions must be enforced server-side, and admin mutations should call `AuditLogService::log()`.

## Testing And Env Gotchas
- `phpunit.xml` uses in-memory SQLite for tests and forces `QUEUE_CONNECTION=sync` plus `SESSION_DRIVER=array`.
- `tests/bootstrap.php` creates a temporary `.env` with only an `APP_KEY` if no `.env` exists, so tests can boot without local env setup.
- The main end-to-end coverage is `tests/Feature/AuthOnboardingTest.php`; it seeds roles, interests, and goals and exercises registration, verification, onboarding, referral awards, and `/home` access.

## Tooling Gotcha
- Composer `post-autoload-dump` runs `scripts/patch-laravel-framework-config.php` before package discovery and `artisan filament:upgrade`; do not remove or bypass that vendor patch casually.
