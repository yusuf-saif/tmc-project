# The Muhsinat Club

A members-only Islamic women's community platform built on Laravel 11, Livewire 3, Alpine.js, and Filament 3. Manages the full membership journey — discovery, application, admin review, payment, and an active member experience with events, resources, journaling, a community marketplace (Souq), and a Jannah Coins wallet.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4 · Laravel 11 · Fortify |
| Frontend | Livewire 3 · Alpine.js · Vite · Tailwind CSS |
| Admin | Filament 3 |
| Database | PostgreSQL (production) · SQLite (local / tests) |
| Storage | Cloudflare R2 (S3-compatible) |
| Mail | Brevo HTTPS API (`symfony/brevo-mailer`) |
| Payments | Paystack |
| Push | Web Push (VAPID — `minishlink/web-push`) |
| Error tracking | Sentry |
| Deployment | Railway (Nixpacks) |

---

## Three Surfaces

| Surface | URL | Who |
|---|---|---|
| Public landing page | `/` | Visitors |
| Member app | `/home`, `/events`, `/resources`, `/journal`, `/souq`, `/community`, `/profile` | Authenticated members |
| Admin panel | `/admin` | Staff (admin, moderator, content_editor, super_admin) |

---

## Local Development

### Prerequisites

- PHP 8.4+
- Composer
- Node.js / npm

### Setup

```bash
# Install dependencies
composer install && npm install

# Copy env and configure
cp .env.example .env
# Set DB_DATABASE to an absolute path for your local .sqlite file

# Generate app key
php artisan key:generate

# Migrate and seed
php artisan migrate --seed

# Link storage
php artisan storage:link

# Start everything (server + queue + Vite + logs)
composer dev
```

**Local admin:** `admin@themuhsinatclub.com` / `Change1234!` at `http://127.0.0.1:8000/admin`

---

## Commands

| Command | Purpose |
|---|---|
| `composer dev` | Full local stack (server + queue + Vite + log watcher) |
| `npm run dev` | Vite only |
| `npm run build` | Production assets |
| `./vendor/bin/pint` | Format PHP |
| `php artisan test` | Full test suite |
| `php artisan test --filter AuthOnboardingTest` | Auth/onboarding regression |
| `php artisan test --filter "PaymentRecordTest\|PaystackWebhookTest\|MembershipBillingTest"` | Payment regression |
| `php artisan migrate --seed` | Seed local data |
| `php artisan db:seed --class=PlaywrightSeeder` | Seed Playwright login user |
| `npm run test:e2e` | Playwright end-to-end (app must be running at `:8000`) |

---

## Key Concepts

### Membership Lifecycle

Members move through a state machine tracked in `member_profiles.onboarding_status`:

```
registered → onboarding → under_review → active → [payment] → member
                                       ↘ needs_correction ↗
                                                              ↓
                                                          suspended
```

Transitions are enforced in `MembershipStateService`. All changes are logged to `audit_logs`.

### Queues

**Three queues:** `default`, `membership`, `billing`

The queue worker **must** be running in production or all emails and push notifications will silently pile up:

```bash
php artisan queue:work --queue=default,membership,billing --sleep=3 --tries=3 --timeout=600
```

### File Uploads

User files go directly to Cloudflare R2 via presigned PUT URLs (browser-to-R2, no PHP involved). Set `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=r2` in production.

### Email

Mail uses Brevo's HTTPS API — **not SMTP** (SMTP is blocked at the network level on Railway). Never add `MAIL_HOST`/`MAIL_PORT`.

---

## Testing

Tests use in-memory SQLite and `QUEUE_CONNECTION=sync`. No external services required.

```bash
php artisan test
```

There is no `MemberProfileFactory` — create test members using `updateOrCreate` on the relation:

```php
$this->seed(RoleSeeder::class);
$user = User::factory()->create(['status' => 'active']);
$user->assignRole('member');
$user->memberProfile()->updateOrCreate(
    ['user_id' => $user->id],
    ['onboarding_status' => 'member', 'current_period_ends_at' => now()->addDays(30)]
);
```

---

## Deployment (Railway)

Railway auto-detects the web process via Nixpacks. The `Procfile` defines the queue worker. A separate cron service runs `php artisan schedule:run` every minute.

**First deploy sequence:**

```bash
railway run "php artisan migrate --force"
railway run "php artisan db:seed --class=RoleSeeder --force"
railway run "php artisan db:seed --class=AdminUserSeeder --force"
railway run "php artisan optimize:clear"
railway run "php artisan permission:cache-reset"
```

**PHP version** is pinned in `nixpacks.toml` (`php84`) and `composer.json` (`"php": "^8.4"`) — both must stay in sync.

---

## Documentation

Full technical reference is in [`docs/`](./docs/):

| File | Contents |
|---|---|
| [`TECHNICAL_REFERENCE.md`](./docs/TECHNICAL_REFERENCE.md) | Architecture, routes, models, services, events, jobs, payments, deployment, env vars |
| [`TRD.md`](./docs/TRD.md) | Technical requirements document |
| [`PRD.md`](./docs/PRD.md) | Product requirements |
| [`BUILD_PHASES.md`](./docs/BUILD_PHASES.md) | Feature build plan |
| [`DESIGN_GUIDE.md`](./docs/DESIGN_GUIDE.md) | UI/UX design guidelines |
| [`DESIGN_SYSTEM.md`](./docs/DESIGN_SYSTEM.md) | Design system tokens and components |
| [`DEPLOYMENT_CHECKLIST.md`](./docs/DEPLOYMENT_CHECKLIST.md) | Deploy checklist |
| [`AUDIT_REPORT.md`](./docs/AUDIT_REPORT.md) | Security and code audit |

For AI agent context and project-specific rules, see [`AGENTS.md`](./AGENTS.md).
