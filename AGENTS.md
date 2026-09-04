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
- Payments regression: `php artisan test --filter "PaymentRecordTest|PaystackWebhookTest|MembershipBillingTest"`
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
- Client IP: forwarded headers are NOT trusted by default. The real client IP comes from Railway's `X-Real-IP` header via `App\Http\Middleware\TrustRealIpHeader` (global, in `bootstrap/app.php`). Opt into `X-Forwarded-*` trust for a known proxy only via `TRUSTED_PROXIES` (comma-separated CIDRs, parsed in `bootstrap/app.php`). Railway does not publish stable proxy CIDR ranges — if Railway's infrastructure changes, update `TRUSTED_PROXIES` in the Railway dashboard. Do not re-add `at: '*'` in `bootstrap/app.php`.
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

## Vendor Corruption Runbook
**If the app boots to a white screen, or a deployment crashes right after `composer install`, with a PHP parse/class error from `vendor/`:**
1. Read the actual error first — it is usually `ParseError`/`Class not found`/`Interface not found` pointing at a specific vendor file (e.g. `intervention/image` `IMAGE_DECODERS`, or a Filament/Laravel class).
2. The fix is never "patch vendor". Regenerate it: `composer install --no-interaction --prefer-dist --optimize-autoloader`.
3. If a single package is clearly at fault, pin it to the version that works: `composer require <package>:<known-good-version>` then repeat step 2.
4. Clear Laravel's compiled caches, which can hold stale class maps: `php artisan optimize:clear`.
5. On Railway, ephemeral filesystem means vendor is rebuilt every deploy — run the rebuild in Railway dashboard (not locally) after bumping `composer.lock`, and confirm the new commit actually contains the lock bump.
6. Never commit `vendor/` changes; if the parse error only exists locally, your `vendor/` is stale — run step 2 locally and re-check `git status` is clean.

## Production Mail (Resend)
- Mailer is `resend` by default (`config/mail.php`); key comes from `RESEND_API_KEY` in `config/services.php` (NOT `RESEND_KEY`).
- The key must be a full-access Resend API key (or a scoped key with `email:send` permission). A read-only/`email:view` key makes every send fail with a `TransportException`.
- `MAIL_FROM_ADDRESS` should stay on the `themuhsinatclub.com` domain (default `noreply@themuhsinatclub.com`); the domain must be verified in Resend or sends bounce/fail.
- Symptom of a bad key/domain: `Symfony\Component\Mailer\Exception\TransportException` ("Failed to send... 401/403") surfacing in the queue worker logs.
- Fix sequence: update `RESEND_API_KEY` in Railway, then `railway run "php artisan optimize:clear"` and retry a queued notification; check `railway logs --service worker`.

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

## Payments
- Every successful or attempted membership payment creates a row in `payment_records` (`app/Models/PaymentRecord.php`): user, member profile, Paystack `external_reference`, provider (`paystack`|`manual`), `billing_cycle` (immutable — set once at record creation from the declared cycle), channel, `amount_kobo`, currency, status (`pending`|`paid`|`failed`), failure reason, `paid_at`.
- `App\Services\MembershipStateService::recordPayment()` accepts an optional `PaymentRecord` and marks it `paid` inside a `lockForUpdate()` transaction that checks `status === 'paid'` for idempotency. It uses `transition()` for the state change (`onboarding`/`active`/`suspended` → `member`), sets `current_period_ends_at` from the record's immutable `billing_cycle` (monthly=30, quarterly=90, yearly=365 days), clears `reminder_sent_at` and `grace_period_ends_at`, and syncs user status to `active`.
- `findOrCreatePaymentRecord()` looks up by `external_reference` first and attaches the profile — the Paystack webhook resolves the profile through this record, falling back to the legacy `member_profiles.paystack_reference` column. The `billing_cycle` parameter is captured once at creation and never mutated afterward.
- Manual/bank-transfer verification in `app/Filament/Pages/ManagePayments.php` and `ViewMembershipApplication.php` also goes through `recordPayment` so a `manual` record is written. Both paths produce identical end states.
- Webhook idempotency is enforced by `PaymentRecord.status` + `lockForUpdate()` — not by `payment_verified_at`. A new reference from a renewing member is always processed (extends period, reactivates if suspended), while a duplicate delivery of the same reference no-ops.

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

## File Uploads (Direct-to-R2)
- **Symptom:** Filament FileUpload fields targeting the `r2` disk fail with a 401 on `/livewire/upload-file`, showing a generic "failed to upload" error in the UI. The root cause class is Livewire's local temp-upload flow being fragile in ephemeral container environments (signed URLs depend on `APP_KEY` and local temp files that don't survive redeploys cleanly).
- **Fix:** Livewire's temporary upload disk is configured via `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` env var (parsed in `config/livewire.php`). When set to `r2`, Livewire generates a presigned PUT URL and the browser uploads directly to R2 — the file never touches the PHP server. This eliminates the entire 401 failure class.
- **Env vars:** `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=r2` (set in Railway dashboard) and `FILESYSTEM_DISK=r2` (already set). Both are in `.env.example`.
- **All R2-targeting FileUpload fields** (fixed by the Livewire config change — no per-field code changes needed):
  - `EventResource` — `cover_image_path` → `r2`, directory `events/covers`
  - `SouqListingResource` — `logo_path` → `r2`, directory `souq/logos`
  - `BadgeResource` — `icon_path` → `r2`, directory `badges/icons`
  - `CommunitySpaceResource` — `cover_image_path` → `r2`, directory `community/covers`
  - `ResourceResource` — `file_path` → `r2`, directory `resources/files`
  - `ResourceResource` — `thumbnail_path` → `r2`, directory `resources/thumbnails`
  - `ListUsers` — `csv_file` → `local` (correctly stays local; temp goes to R2, final copy to local)
- **Auto-setup on deploy:** `php artisan r2:setup` runs automatically during Railway deploys (in `railway.json` build command). It configures:
  - **CORS policy** on the R2 bucket via Cloudflare API (requires `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ACCOUNT_ID` in `.env`). Allows `GET, PUT, POST, HEAD` from `APP_URL`, `127.0.0.1:8000`, and `localhost:8000`.
  - **Lifecycle rule** on `livewire-tmp/` prefix via S3 API — auto-deletes temp files after 24 hours. Idempotent (safe to run on every deploy).
  - If Cloudflare credentials are not set, CORS is skipped with a warning (not a deploy failure). The lifecycle rule requires only the existing R2/S3 credentials.
- **Manual run:** `php artisan r2:setup` (or `--cors-only` / `--lifecycle-only` flags).
- **After deploy:** run `php artisan config:clear` to pick up the new env var.
- **Test coverage:** `tests/Feature/FileUploadDiskConfigTest.php` verifies config resolution, disk driver, file visibility, and that all 6 R2-targeting FileUpload fields target the correct disk.
