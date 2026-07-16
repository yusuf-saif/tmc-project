# TMC Repo Notes For OpenCode

## Ignore First
- `README.md` is still the stock Laravel README. Treat `routes/`, `app/`, config files, and this file as the real source of truth.

## App Surface
- This is one Laravel 11 app with three surfaces: public landing page at `/`, member app on Livewire routes in `routes/web.php`, and Filament admin at `/admin`.
- `GET /` must keep serving `resources/views/landing.blade.php`. That page is a large inline-styled Blade file, not a normal Vite/Tailwind screen.
- Member routes are not controller-based by default; most screens are mounted directly as Livewire components from `routes/web.php`.
- `/onboarding` is behind `auth` only. `/home` and the rest of the member area use the `ensure.user.state` middleware alias from `bootstrap/app.php`.

## Commands
- Install deps: `composer install && npm install`
- Full local stack: `composer dev`
- Frontend only: `npm run dev`
- Production assets: `npm run build`
- Format PHP: `./vendor/bin/pint`
- Full test suite: `php artisan test`
- Focused auth/onboarding regression: `php artisan test --filter AuthOnboardingTest`
- Seed local data set: `php artisan migrate --seed`
- Seed Playwright login user: `php artisan db:seed --class=PlaywrightSeeder`
- Playwright expects the app at `http://127.0.0.1:8000`: `npm run test:e2e`

## Railway Deployment
- Deploy from GitHub repo — Nixpacks auto-detects Laravel web process (PHP-FPM + Nginx, doc root `public/`)
- Worker: `Procfile` defines `php artisan queue:work --sleep=3 --tries=3 --timeout=600`. **The worker is mandatory in production** — without it, queued notifications (import completion, billing, membership emails) silently accumulate in the `jobs` table and are never sent.
- Scheduler: `railway.json` runs `php artisan schedule:run` every minute via cron
- Database: Railway PostgreSQL plugin (auto-injects `DATABASE_URL` — manually set `DB_CONNECTION=pgsql` and individual env vars)
- Storage: Railway Tigris (S3-compatible) — set `FILESYSTEM_DISK=s3` and AWS env vars
- Env vars: copy from `.env.example`, fill in all production secrets in Railway dashboard
- Repo-level env overrides: `railway.json.build.buildCommand` runs `npm ci && npm run build && php artisan storage:link && php artisan optimize` on each deploy
- Remote Artisan: `railway run "php artisan migrate --force"`
- Remote seed: `railway run "php artisan db:seed --class=RoleSeeder --force"`
- Remote shell: `railway shell`
- Remote logs: `railway logs`
- Production fix sequence (run sequentially via `railway run`):
  ```
  php artisan migrate --force
  php artisan db:seed --class=RoleSeeder --force
  php artisan db:seed --class=AdminUserSeeder --force
  php artisan optimize:clear
  php artisan permission:cache-reset
  ```

## Queue Names
- Canonical queue set: `default`, `membership`, `billing`
- Worker start command (Procfile): `php artisan queue:work --queue=default,membership,billing --sleep=3 --tries=3 --timeout=600`
- Queue config: `config/queue.php` (database driver by default)
- Railway worker must run with the same queue names as defined in Procfile

## Scheduler / Cron
- Railway cron: `railway.json` defines `* * * * *` → `php artisan schedule:run`
- Scheduler entries in `routes/console.php`
- All times are UTC (configurable via `APP_TIMEZONE` env var, default UTC)
- Railway cron service: create a service in Railway dashboard with:
  - Start command: `php artisan schedule:run`
  - Cron schedule: `* * * * *`
  - Same env vars as the web/worker services

## Queue Health Runbook
**If emails or notifications are delayed:**
1. Check worker service logs: `railway logs --service worker`
2. Check jobs count: `railway run "php artisan tinker --execute=\"echo \\DB::table('jobs')->count();\""`
3. Check failed jobs: `railway run "php artisan tinker --execute=\"echo \\DB::table('failed_jobs')->count();\""`
4. Run health sweep manually: `railway run "php artisan queue:health-sweep"`
5. If worker is down: restart it in Railway dashboard or `railway service restart worker`

## Auth And Redirects
- Fortify uses custom Blade views in `resources/views/auth/*`; do not swap in starter-kit assumptions.
- Login redirect logic is custom in `app/Http/Responses/FortifyLoginResponse.php`: admin-capable roles go to `/admin`, members without completed onboarding go to `/onboarding`, everyone else goes to `/home`.
- Register redirect is custom too: `app/Http/Responses/FortifyRegisterResponse.php` sends browser requests straight to `/home`.
- Referral awards are wired off `App\Events\MembershipActivated` in `app/Providers/AppServiceProvider.php`.

## Brand And UI Constraints
- Keep landing-page styling decisions in `resources/views/landing.blade.php` unless the task is specifically about that page.
- Shared app/auth styling tokens live in `resources/css/app.css` and `tailwind.config.js`; match those tokens instead of default Laravel or Filament styling.
- Brand image names are fixed in `public/images/`: landing uses `img1`-`img4`, and Filament branding uses `img1.png` and `img2.png` in `AdminPanelProvider`.

## Data, Roles, And Permissions
- Roles are seeded by `database/seeders/RoleSeeder.php`: `super_admin`, `admin`, `moderator`, `content_editor`, `volunteer`, `member`.
- Local admin seed is `admin@themuhsinatclub.com` / `Change1234!` from `database/seeders/AdminUserSeeder.php`.
- Filament access is gated in `App\Models\User::canAccessPanel()` to `super_admin`, `admin`, `moderator`, and `content_editor`.
- Journal privacy is enforced in code: `App\Models\JournalEntry` casts `body` as `encrypted`.
- Server-side authorization is required. Example: `JournalEntryPolicy` is registered in `app/Providers/AuthServiceProvider.php` and enforced from the Livewire screen.
- Admin-side mutations are expected to call `App\Services\AuditLogService::log()`; existing Filament resources/pages already follow that pattern.

## Tests And Env Gotchas
- `phpunit.xml` uses in-memory SQLite and forces `QUEUE_CONNECTION=sync` and `SESSION_DRIVER=array`; avoid writing tests that depend on MySQL/Postgres-specific behavior unless necessary.
- `tests/bootstrap.php` writes a temporary `.env` with only `APP_KEY` when none exists so tests can boot in a clean checkout.
- `tests/Feature/AuthOnboardingTest.php` is the highest-value feature flow: registration, onboarding, referral wiring, reward issuance, and `/home` protection.
- Playwright uses `tests/e2e/setup/auth.setup.js` to log in as `member@test.com` / `password` and stores state in `tests/e2e/.auth/member.json`.

## Tooling And Deploy Gotchas
- Composer `post-autoload-dump` runs `scripts/patch-laravel-framework-config.php` before package discovery and `artisan filament:upgrade`; do not remove or bypass that patch casually.
- `railway.json` is the checked-in deploy recipe. Production there is Railway with PostgreSQL, while tests/local are SQLite.
- Railway filesystem is ephemeral — `public/storage` symlink is recreated on each deploy via the build command; uploaded files (avatars, event covers, etc.) must use Tigris (S3-compatible) storage.
- Railway auto-detects Laravel web process via Nixpacks; `Procfile` only defines the queue worker (web process is handled automatically).
- On first deploy, run the fix sequence in Railway Commands above to seed roles and admin user.

## Product Docs
- For feature work, read `docs/BUILD_PHASES.md` first, then `docs/TRD.md`, then the relevant UI doc in `docs/DESIGN_GUIDE.md` or `docs/DESIGN_SYSTEM.md`.
