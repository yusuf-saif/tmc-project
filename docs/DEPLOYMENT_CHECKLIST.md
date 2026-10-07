# TMC Deployment Checklist (Railway)

## Pre-Deployment
- [ ] All tests pass: `php artisan test`
- [ ] Push latest code to GitHub (main branch)
- [ ] Review `.env.example` — all required vars listed with Railway defaults
- [ ] VAPID keys generated: `php artisan webpush:vapid`
- [ ] Paystack keys ready (live keys for production)

## Railway Setup (First Time)
- [ ] Connect GitHub repo in Railway dashboard
- [ ] Add PostgreSQL plugin (auto-injects `DATABASE_URL`)
- [ ] Add Tigris plugin (S3-compatible storage, auto-injects AWS vars)
- [ ] Copy all env vars from `.env.example` to Railway Variables panel
- [ ] Set `DB_CONNECTION=pgsql` and individual `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from `DATABASE_URL`
- [ ] Set `FILESYSTEM_DISK=s3`
- [ ] Set `APP_URL=https://yourdomain.com` (or Railway-generated domain)
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Set `CACHE_STORE=database` and `SESSION_DRIVER=database`
- [ ] Set `QUEUE_CONNECTION=database`
- [ ] Set `MAIL_MAILER=resend` and `RESEND_API_KEY`
- [ ] Set Paystack keys: `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_WEBHOOK_SECRET`
- [ ] Set VAPID keys: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`, `VAPID_SUBJECT`
- [ ] Set `SENTRY_LARAVEL_DSN` (optional, for error tracking)

## First Deploy
- [ ] Push to main — Railway auto-deploys via Nixpacks
- [ ] Verify build succeeds (logs in Railway dashboard)
- [ ] Run post-deploy sequence via `railway run`:
  ```
  php artisan migrate --force
  php artisan db:seed --class=RoleSeeder --force
  php artisan db:seed --class=AdminUserSeeder --force
  php artisan optimize:clear
  php artisan permission:cache-reset
  ```

## Domain Setup
- [ ] In Railway dashboard → Service Settings → Domains → Add custom domain
- [ ] In Namecheap (or other registrar) → add CNAME record pointing to Railway target
- [ ] Wait for DNS propagation (5-30 min)
- [ ] Verify HTTPS works (Railway auto-provisions SSL)

## Post-Deploy Verification
- [ ] Public landing page (`/`) loads
- [ ] Registration and login flows work
- [ ] Admin panel (`/admin`) accessible: `admin@themuhsinatclub.com`
- [ ] Image uploads work (avatars, event covers, community space covers, souq logos via Tigris/S3)
- [ ] Queue worker running: check `railway logs` for worker output
- [ ] Scheduler running: verify cron jobs fire (grace periods, renewal reminders)
- [ ] Push notifications: browser prompt and subscription work
- [ ] Email sending: verify Resend.com delivers
- [ ] PWA: install on Android / iOS, offline page loads
- [ ] Lighthouse audit: PWA score 80+

## Ongoing Maintenance
- [ ] Monitor `railway logs` for errors
- [ ] Update env vars via Railway dashboard (auto-restarts service)
- [ ] Run migrations on schema changes: `railway run "php artisan migrate --force"`
- [ ] Clear cache after config changes: `railway run "php artisan optimize:clear"`
