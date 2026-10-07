# The Muhsinat Club — Project Documentation

> **Version:** October 2026  
> **Stack:** Laravel 11 · PHP 8.4 · Livewire 3 · Alpine.js · Filament 3 · Tailwind CSS · PostgreSQL (prod) / SQLite (local)

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture](#2-architecture)
3. [Directory Structure](#3-directory-structure)
4. [Surfaces & Routes](#4-surfaces--routes)
5. [Authentication & Access Control](#5-authentication--access-control)
6. [Membership Lifecycle](#6-membership-lifecycle)
7. [Data Models](#7-data-models)
8. [Services Layer](#8-services-layer)
9. [Events & Listeners](#9-events--listeners)
10. [Jobs & Queues](#10-jobs--queues)
11. [Notifications](#11-notifications)
12. [Scheduler (Cron)](#12-scheduler-cron)
13. [Artisan Commands](#13-artisan-commands)
14. [Payments — Paystack & Manual](#14-payments--paystack--manual)
15. [Filament Admin Panel](#15-filament-admin-panel)
16. [Livewire Member App](#16-livewire-member-app)
17. [PWA & Push Notifications](#17-pwa--push-notifications)
18. [File Storage (Cloudflare R2)](#18-file-storage-cloudflare-r2)
19. [Email — Brevo HTTPS API](#19-email--brevo-https-api)
20. [Security](#20-security)
21. [Database Schema](#21-database-schema)
22. [Testing](#22-testing)
23. [Local Development](#23-local-development)
24. [Deployment (Railway)](#24-deployment-railway)
25. [Environment Variables](#25-environment-variables)

---

## 1. Project Overview

The Muhsinat Club (TMC) is a members-only Islamic women's community platform. The app manages the full membership journey — from landing page discovery, through a multi-step signup wizard, admin review, payment, and into an active member experience with events, resources, journaling, a community marketplace (Souq), and a gamified Jannah Coins wallet.

**Three surfaces in one Laravel app:**

| Surface | URL prefix | Audience |
|---|---|---|
| Public landing page | `/` | Visitors |
| Member app | `/home`, `/events`, `/resources`, `/journal`, `/souq`, `/community`, `/profile` | Authenticated members |
| Admin panel | `/admin` | Staff (super_admin, admin, moderator, content_editor) |

---

## 2. Architecture

```
Browser
  │
  ├── GET /          → Blade (landing.blade.php, no Livewire, no Vite)
  │
  ├── /admin/*       → Filament 3 (SPA-style, server-rendered)
  │
  └── /home, /events, etc.
         │
         └── Livewire 3 full-page components
               mounted from routes/web.php
               wrapped by resources/views/layouts/app.blade.php
               Alpine.js (global) for client-side interactivity
               Vite → resources/js/app.js + resources/css/app.css
```

**Key architectural decisions:**

- **No controller layer** for member routes — Livewire components are mounted directly from routes (`Route::get('/home', HomeDashboard::class)`).
- **Service layer** (`app/Services/`) owns all business logic. Livewire components and Filament pages call services; they do not contain business logic themselves.
- **Event-driven side effects** — payments, approvals, activations etc. fire Laravel Events. Listeners handle notifications, coin awards, audit logs, and push notifications.
- **Queue-first** — all emails and push notifications are dispatched to queued jobs so the HTTP response is never blocked.

---

## 3. Directory Structure

```
tmc-project/
├── app/
│   ├── Console/Commands/      # Artisan commands (grace periods, reminders, etc.)
│   ├── Events/                # Domain events (MembershipActivated, etc.)
│   ├── Filament/
│   │   ├── Pages/             # Custom Filament pages (Dashboard, ManagePayments, Settings)
│   │   ├── Resources/         # CRUD resources for admin panel
│   │   └── Widgets/           # Dashboard stats & activity widgets
│   ├── Http/
│   │   ├── Controllers/       # Traditional controllers (webhooks, payment status, receipt)
│   │   ├── Middleware/        # SecurityHeaders, EnsureUserState, TrustRealIpHeader
│   │   └── Responses/         # Custom Fortify login/register/email-verify responses
│   ├── Jobs/                  # Queued jobs (broadcasts, newsletters, event reminders)
│   ├── Listeners/             # Event listeners (notifications, coins, audit logs)
│   ├── Livewire/              # Full-page & nested Livewire components (member app)
│   ├── Models/                # Eloquent models
│   ├── Notifications/         # Laravel notification classes (email + push)
│   ├── Policies/              # Authorization policies
│   ├── Providers/             # AppServiceProvider, FortifyServiceProvider
│   └── Services/              # Business logic layer
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── css/app.css            # Design tokens + global styles
│   ├── js/app.js              # Alpine.js, Livewire, Vite entry
│   └── views/
│       ├── auth/              # Fortify auth views (login, register, etc.)
│       ├── community/         # donate.blade.php
│       ├── layouts/app.blade.php  # Member app shell (PWA banners, push, nav)
│       ├── livewire/          # Blade views for Livewire components
│       ├── partials/          # Reusable partials (ios-install-instructions, etc.)
│       └── landing.blade.php  # Public landing page (standalone, no Vite)
├── routes/
│   ├── web.php                # All HTTP routes
│   └── console.php            # Scheduled tasks
├── tests/
│   ├── Feature/               # HTTP & integration tests
│   └── e2e/                   # Playwright end-to-end tests
├── Procfile                   # Railway worker definition
├── railway.json               # Railway deploy config
└── nixpacks.toml              # PHP version pin for Railway
```

---

## 4. Surfaces & Routes

### 4.1 Public Routes

| Route | Handler | Notes |
|---|---|---|
| `GET /` | `view('landing')` | Static Blade, no auth |
| `GET /membership/signup` | `MembershipSignupWizard` (Livewire) | Multi-step wizard, creates user internally |
| `GET /onboarding` | `OnboardingController@showForm` | Token-gated, for CSV-imported members |
| `POST /onboarding/complete` | `OnboardingController@complete` | Completes imported-member setup |
| `POST /webhooks/paystack` | `PaystackWebhookController` | CSRF-exempt, HMAC-verified |

### 4.2 Auth-Only (any logged-in user)

| Route | Handler |
|---|---|
| `POST /push/subscribe` | `PushSubscriptionController@store` |
| `DELETE /push/subscribe` | `PushSubscriptionController@destroy` |
| `GET /membership/pending` | `PendingReview` (Livewire) |
| `GET /membership/payment` | `PaymentPage` (Livewire) |
| `GET /membership/payment/status` | `PaymentStatusController@check` |

### 4.3 Member App (auth + ensure.user.state middleware)

| Route | Handler |
|---|---|
| `/home` | `HomeDashboard` |
| `/events` | `EventsList` |
| `/events/{slug}` | `EventDetail` |
| `/resources` | `ResourcesLibrary` |
| `/resources/{slug}` | `ResourceDetail` |
| `/community` | `CommunityHome` |
| `/community/spaces/{slug}` | `SpaceDetail` |
| `/community/support/{type}` | `SupportForm` |
| `/community/donate` | Inline closure (reads `Setting`) |
| `/profile` | `ProfileScreen` |
| `/profile/edit` | `EditProfile` |
| `/profile/legacy-card` | `LegacyCard` |
| `/profile/notifications` | `NotificationPreferences` |

**Restricted (also requires `not-suspended` middleware):**

| Route | Handler |
|---|---|
| `/journal` | `JournalScreen` |
| `/souq` | `SouqDirectory` |
| `/souq/apply` | `ApplyForm` |
| `/souq/{slug}` | `ListingDetail` |

---

## 5. Authentication & Access Control

### Auth Stack
- **Laravel Fortify** handles registration, login, email verification, and password reset.
- Custom Blade views in `resources/views/auth/` (no starter-kit assumptions).
- Custom Fortify response classes override redirect logic:

| Class | Behaviour |
|---|---|
| `FortifyLoginResponse` | Admin roles → `/admin`; no onboarding → `/onboarding`; else → `/home` |
| `FortifyRegisterResponse` | Browser requests → `/home` directly |
| `FortifyVerifyEmailResponse` | `/home` after verification |

### Roles (via `spatie/laravel-permission`)

| Role | Access |
|---|---|
| `super_admin` | Full admin panel + all operations |
| `admin` | Admin panel |
| `moderator` | Admin panel |
| `content_editor` | Admin panel |
| `volunteer` | Member app only |
| `member` | Member app only |

Roles are seeded by `database/seeders/RoleSeeder.php`. Local admin seed: `admin@themuhsinatclub.com` / `Change1234!`.

### Middleware

| Alias | Class | Purpose |
|---|---|---|
| `ensure.user.state` | `EnsureUserStateRedirect` | Routes suspended/pending/unverified users to the right page |
| `not-suspended` | `EnsureNotSuspendedFromRestrictedAreas` | Blocks suspended members from journal, Souq |

---

## 6. Membership Lifecycle

The `onboarding_status` column on `member_profiles` is the single source of truth for where a member is in the journey.

```
registered          ← User created (signup wizard or CSV import)
     │
     ▼
onboarding          ← Profile form submitted (wizard step 2)
     │
     ▼
under_review        ← Application submitted to admin
     │
  ┌──┴──────────────┐
  ▼                 ▼
active          needs_correction   ← Admin requests changes
  │                 │
  │                 └──────────────► under_review (resubmit)
  ▼
[payment required]
  │
  ▼
member              ← Payment verified, membership active
  │
  ├──────────────────► suspended   ← Grace period expired or manual suspension
  │                        │
  └────────────────────────┘        ← Renewal payment reactivates
```

**State transitions are enforced in `MembershipStateService::transition()`** — invalid transitions throw an exception. All transitions are logged to `audit_logs`.

### Billing Cycles

| Cycle | Duration | Default Fee (Naira) |
|---|---|---|
| `monthly` | 30 days | 5,000 |
| `quarterly` | 90 days | 12,000 |
| `yearly` | 365 days | 40,000 |

Fees are configurable via `settings` table (Filament Settings page). Amounts are stored in `payment_records.amount_kobo` (kobo = Naira × 100).

### Grace Period
When `current_period_ends_at` passes, the scheduler sets `grace_period_ends_at` to `current_period_ends_at + grace_period_days`. When that also passes without a renewal payment, the member is automatically suspended.

---

## 7. Data Models

### Core Models

| Model | Table | Purpose |
|---|---|---|
| `User` | `users` | Auth entity; has roles, referral code, status |
| `MemberProfile` | `member_profiles` | All membership data; `onboarding_status` FSM |
| `PaymentRecord` | `payment_records` | Every payment attempt (Paystack or manual) |
| `MembershipSerial` | `membership_serials` | Auto-increments per Hijri year + type for membership IDs |

### Community & Content

| Model | Table | Purpose |
|---|---|---|
| `Event` | `events` | Halaqahs, webinars, meetups |
| `EventRsvp` | `event_rsvps` | Member RSVPs |
| `Resource` | `resources` | Library of Islamic resources (files/links) |
| `CommunitySpace` | `community_spaces` | Sub-groups / circles |
| `JournalEntry` | `journal_entries` | Private encrypted journal (`body` cast `encrypted`) |
| `DuaListItem` | `dua_list_items` | Personal du'a tracker |
| `SouqListing` | `souq_listings` | Business directory listings |
| `Subscription` | `subscriptions` | Souq business billing |

### Engagement & Gamification

| Model | Table | Purpose |
|---|---|---|
| `JannahCoinsLedger` | `jannah_coins_ledger` | Coin transaction log (append-only) |
| `Badge` | `badges` | Achievement badges |
| `UserBadge` | `user_badges` | Awarded badges (unique per user+badge) |
| `UserReferral` | `user_referrals` | Referral chain records + coin rewards |

### System

| Model | Table | Purpose |
|---|---|---|
| `AuditLog` | `audit_logs` | Immutable event log for all admin actions |
| `Setting` | `settings` | Key-value config (fees, grace period, etc.) |
| `InAppAnnouncement` | `in_app_announcements` | Homepage banners with dismissal tracking |
| `Broadcast` | `broadcasts` | Admin push-notification broadcasts |
| `Newsletter` | `newsletters` | Email newsletter campaigns |
| `PushSubscription` | `push_subscriptions` | Web Push VAPID subscriptions |

### Key Field Notes

- `JournalEntry.body` — cast `encrypted`, decrypted transparently by Eloquent
- `User.email` — always stored lowercase (boot-time `saving` hook + normalization on boot)
- `PaymentRecord.billing_cycle` — immutable after creation; never updated
- `PaymentRecord.external_reference` — Paystack reference, used for idempotency
- `MemberProfile.membership_id` — format: `TMC/{TYPE}/{HIJRI_YEAR}/{SERIAL}`, e.g. `TMC/M/1446/0042`
- `MemberProfile.membership_type` — `M` (Member), `SM` (SixteenMember), `E` (Executive)

---

## 8. Services Layer

All business logic lives in `app/Services/`. Livewire components and Filament pages inject and call these.

| Service | Responsibility |
|---|---|
| `MembershipStateService` | FSM for `onboarding_status` transitions; `approve()`, `reject()`, `recordPayment()`, `suspend()`, `reactivate()`, `checkGracePeriod()` |
| `MembershipSignupService` | Creates `User` + `MemberProfile` + draft during signup wizard |
| `MembershipApprovalService` | Admin workflow: submit for review, approve, reject, needs-correction |
| `MembershipIdService` | Generates membership IDs (`TMC/M/1446/0042`) using `membership_serials` table |
| `PaystackService` | Initiates Paystack payment, verifies transactions, handles webhook payload parsing |
| `CoinsService` | Awards Jannah Coins; appends to ledger; idempotent (checks for existing record) |
| `AuditLogService` | `log($action, $targetType, $targetId, $metadata)` — called by Filament on every mutation |
| `NotificationService` | Centralised wrapper for dispatching notifications to correct channels |
| `PushNotificationService` | Sends Web Push via `minishlink/web-push`; auto-removes expired subscriptions |
| `ImageProcessingService` | Resizes/optimises uploaded images before storing to R2 |
| `BusinessStateService` | Souq listing subscription transitions (approve, activate, suspend, auto-expire) |
| `SubscriptionStateService` | Souq subscription billing lifecycle |
| `RsvpService` | Event RSVP creation, cancellation, seat limit enforcement |
| `HijriDateService` | Converts Gregorian dates to Hijri using `alkoumi/laravel-hijri-date` |
| `DuaListService` | CRUD for personal du'a list items |
| `MembersCsvImportService` | Parses CSV, creates/updates users, dispatches invitation emails |

---

## 9. Events & Listeners

Events fire from services. Listeners are registered in `AppServiceProvider::boot()`.

### Membership Events

| Event | Listeners |
|---|---|
| `MembershipSubmitted` | `LogMembershipEvent`, `SendMembershipNotifications` |
| `MembershipApproved` | `LogMembershipEvent`, `SendMembershipNotifications` |
| `MembershipRejected` | `LogMembershipEvent`, `SendMembershipNotifications` |
| `MembershipNeedsCorrection` | `LogMembershipEvent`, `SendMembershipNotifications` |
| `MembershipActivated` | `AwardReferralCoins`, `AwardWelcomeCoins`, `LogBillingEvent`, `SendMembershipNotifications` |
| `MemberOnboardingCompleted` | `AwardWelcomeCoins`, `SendWelcomePush`, `LogOnboardingEvent` |

### Billing / Subscription Events

| Event | Listeners |
|---|---|
| `SubscriptionActivated` | `LogBillingEvent`, `SendBillingNotifications` |
| `SubscriptionExpiringSoon` | `LogBillingEvent`, `SendBillingNotifications` |
| `SubscriptionPaymentReceived` | `LogBillingEvent`, `SendBillingNotifications` |
| `SubscriptionPaymentFailed` | `LogBillingEvent`, `SendBillingNotifications` |
| `SubscriptionSuspended` | `LogBillingEvent`, `SendBillingNotifications` |
| `SubscriptionExpired` | `LogBillingEvent`, `SendBillingNotifications` |

### Souq Business Events

| Event | Listeners |
|---|---|
| `BusinessApproved` | `LogBillingEvent`, `SendBillingNotifications` |
| `BusinessActivated` | `LogBillingEvent`, `SendBillingNotifications` |
| `BusinessSuspended` | `LogBillingEvent`, `SendBillingNotifications` |

### Auth Events

| Event | Listeners |
|---|---|
| `Illuminate\Auth\Events\Failed` | `LogFailedLogin` |

---

## 10. Jobs & Queues

**Canonical queues:** `default`, `membership`, `billing`

Worker command (defined in `Procfile`):
```bash
php artisan queue:work --queue=default,membership,billing --sleep=3 --tries=3 --timeout=600
```

> **Warning:** The queue worker **must** be running in production. Without it, all emails, push notifications, and import completions accumulate silently in the `jobs` table.

| Job | Queue | Purpose |
|---|---|---|
| `SendBroadcastNotificationJob` | `default` | Sends admin broadcast push notifications to all subscribers |
| `SendNewsletterEmailJob` | `default` | Sends a newsletter email to a recipient |
| `SendEventReminderNotification` | `default` | Event reminder push/email 24h before |
| `ImportMembersJob` | `default` | Processes a CSV member import; sends completion notification |

---

## 11. Notifications

All notification classes extend Laravel's `Notification`. They use the `mail` channel (Brevo HTTPS API) and/or the `database` channel.

| Notification | Trigger |
|---|---|
| `MembershipApplicationSubmitted` | Member submits application |
| `MembershipApproved` | Admin approves application |
| `MembershipRejected` | Admin rejects application |
| `MembershipNeedsCorrection` | Admin flags for correction |
| `MembershipPaymentConfirmed` | Payment verified |
| `MembershipRenewalReminder` | Scheduler (7 days before expiry) |
| `MembershipUnderReviewNotification` | Application enters review queue |
| `OnboardingInvitationNotification` | CSV import invitation to set password |
| `SetPasswordNotification` | Password reset / initial set |
| `SubscriptionActivated` | Souq subscription activated |
| `SubscriptionExpired` | Souq subscription expired |
| `SubscriptionExpiringSoon` | 7 days before Souq billing end |
| `SubscriptionPaymentReceived` | Souq payment confirmed |
| `SubscriptionPaymentFailed` | Souq payment failed |
| `SubscriptionSuspended` | Souq listing suspended |
| `BusinessApproved` | Souq listing approved |
| `BusinessActivated` | Souq listing billing activated |
| `BusinessSuspended` | Souq listing billing suspended |
| `EventReminder` | 24h before an event the member RSVPd |
| `ImportCompleteNotification` | Admin: CSV import finished |
| `ImportFailedNotification` | Admin: CSV import failed |
| `QueueHealthAlertNotification` | Queue health sweep detects delay |

---

## 12. Scheduler (Cron)

Defined in `routes/console.php`. Railway runs `php artisan schedule:run` every minute via the cron service.

| Schedule | Command / Task | Time (UTC) |
|---|---|---|
| Daily 00:05 | Expire Souq business billing (past `billing_end_date`) | 00:05 |
| Daily 00:10 | `membership:check-grace-periods` | 00:10 |
| Daily 01:00 | `queue:health-sweep` (with drain) | 01:00 |
| Daily 01:10 | `members:send-pending-invites --limit=90` | 01:10 |
| Daily 08:00 | `membership:send-renewal-reminders` | 08:00 |
| Every 15 min | `queue:health-sweep --no-drain` (alert only) | `*/15` |

---

## 13. Artisan Commands

| Command | Purpose |
|---|---|
| `membership:check-grace-periods` | Checks all active members; sets/enforces grace period; suspends expired |
| `membership:send-renewal-reminders` | Emails members whose subscription ends in ≤ 7 days |
| `members:send-pending-invites [--limit=N]` | Sends onboarding invitation to CSV-imported users not yet invited |
| `member:backfill-referral-codes [--dry-run]` | Generates referral codes for users who have none |
| `queue:health-sweep [--no-drain]` | Checks for delayed jobs; alerts via Sentry + admin notification; optionally drains |
| `badges:fix-icon-paths [--dry-run]` | Normalises badge `icon_path` from full URLs to storage-relative keys |
| `r2:setup [--cors-only\|--lifecycle-only]` | Configures Cloudflare R2 CORS policy + lifecycle rules for temp uploads |

---

## 14. Payments — Paystack & Manual

### Paystack Flow

```
Member clicks "Pay" on /membership/payment
    │
    ▼
PaystackService::initiate() → returns Paystack checkout URL
    │
    ▼
Browser redirects to pay.paystack.co
    │
    ▼
After payment → Paystack POSTs to /webhooks/paystack
    │
    ▼
PaystackWebhookController::__invoke()
    ├── Verifies HMAC-SHA512 signature (PAYSTACK_WEBHOOK_SECRET)
    ├── Resolves PaymentRecord by external_reference
    └── Calls MembershipStateService::recordPayment()
            ├── lockForUpdate() transaction (idempotency)
            ├── Marks PaymentRecord.status = 'paid'
            ├── Transitions onboarding_status → 'member'
            ├── Sets current_period_ends_at
            └── Fires MembershipActivated event
```

### Manual / Bank Transfer Flow

```
Member pays manually → notifies admin
    │
    ▼
Admin opens Filament ManagePayments page
(or ViewMembershipApplication)
    │
    ▼
Admin clicks "Verify Payment"
    │
    ▼
MembershipStateService::recordPayment()
    (same code path as webhook, provider='manual')
```

### PaymentRecord Fields

| Field | Notes |
|---|---|
| `external_reference` | Paystack reference; unique idempotency key |
| `provider` | `paystack` or `manual` |
| `billing_cycle` | **Immutable** — set once at creation, never updated |
| `amount_kobo` | Payment amount in kobo (Naira × 100) |
| `status` | `pending` → `paid` or `failed` |

---

## 15. Filament Admin Panel

**URL:** `/admin`  
**Access:** `super_admin`, `admin`, `moderator`, `content_editor` (not suspended)

### Resources (CRUD)

| Resource | Model | Key features |
|---|---|---|
| `UserResource` | User | List, view, bulk CSV import, role management |
| `MembershipApplicationResource` | MemberProfile | Application review; approve / reject / needs-correction / payment verification |
| `EventResource` | Event | Create/edit, RSVP list, cover image upload to R2 |
| `ResourceResource` | Resource | Library management, file/thumbnail upload to R2 |
| `SouqListingResource` | SouqListing | Business directory moderation, billing management |
| `BadgeResource` | Badge | Badge creation with icon upload |
| `BroadcastResource` | Broadcast | Push-notification broadcasts to all members |
| `NewsletterResource` | Newsletter | Email newsletter campaigns |
| `CategoryResource` | Category | Content categories |
| `InterestResource` | Interest | Interest tags for member profiles |
| `GoalResource` | Goal | Goal options for member profiles |
| `CommunitySpaceResource` | CommunitySpace | Community circles with cover images |
| `InAppAnnouncementResource` | InAppAnnouncement | Homepage banners with scheduled visibility |
| `AuditLogResource` | AuditLog | Read-only audit trail |
| `PaymentRecordResource` | PaymentRecord | Read-only payment history |
| `SupportApplicationResource` | SupportApplication | Zakat/Sadaqah/hardship support applications |

### Custom Pages

| Page | Purpose |
|---|---|
| `Dashboard` | Stats overview + recent activity widgets |
| `ManagePayments` | Bulk payment verification UI |
| `SettingsPage` | Key-value settings editor (fees, grace period, coin rewards) |

### Widgets

| Widget | Purpose |
|---|---|
| `StatsOverviewWidget` | Member counts by status |
| `PendingApplicationsWidget` | Applications awaiting review |
| `LatestApplicationsWidget` | Recent submissions |
| `RecentActivityWidget` | Latest audit log entries |

> **Note:** Every admin mutation must call `AuditLogService::log()`. All existing resources and pages follow this pattern.

---

## 16. Livewire Member App

All components are full-page Livewire components mounted from `routes/web.php`.

### Components

| Component | Route | Description |
|---|---|---|
| `HomeDashboard` | `/home` | Dashboard: upcoming events, new resources, Jannah Coins, quick actions, PWA install card |
| `EventsList` | `/events` | Filterable event listing with RSVP |
| `EventDetail` | `/events/{slug}` | Event details, RSVP button, countdown |
| `ResourcesLibrary` | `/resources` | Searchable resource library |
| `ResourceDetail` | `/resources/{slug}` | Resource detail with file access |
| `JournalScreen` | `/journal` | Private encrypted journal |
| `SouqDirectory` | `/souq` | Public business directory |
| `ApplyForm` | `/souq/apply` | Souq listing application (with R2 file upload) |
| `ListingDetail` | `/souq/{slug}` | Business listing detail |
| `CommunityHome` | `/community` | Community spaces + support links |
| `SpaceDetail` | `/community/spaces/{slug}` | Individual community space |
| `SupportForm` | `/community/support/{type}` | Zakat/Sadaqah/hardship application |
| `ProfileScreen` | `/profile` | Member profile with tabs (wallet, referrals, badges) |
| `EditProfile` | `/profile/edit` | Edit personal info, avatar, social links |
| `MembershipSignupWizard` | `/membership/signup` | Multi-step signup wizard |
| `PaymentPage` | `/membership/payment` | Payment plan selection + Paystack initiation |
| `PendingReview` | `/membership/pending` | Holding screen while under admin review |

### Layout Shell (`resources/views/layouts/app.blade.php`)

The shared wrapper for all member screens:

- Top bar (logo + notification bell)
- `{{ $slot }}` for the Livewire component output
- Bottom navigation bar (Home, Events, Resources, Community, Profile)
- **PWA install banners:**
  - `#install-banner` — Android/Chrome; triggered by `beforeinstallprompt`; shown after 2 visits with 30-day dismissal cooldown
  - `#ios-install-banner` — iOS; UA-detected; shows "Tap Share → Add to Home Screen"
- Toast notifications
- Inline `<script>` with global PWA functions (not a module — all in `window` scope): `installPWA()`, `isIOS()`, `isAlreadyStandalone()`, `isInStandaloneMode()`, `deferredPrompt`

---

## 17. PWA & Push Notifications

### Progressive Web App

| Asset | Location |
|---|---|
| Web Manifest | `public/manifest.json` |
| Service Worker | `public/sw.js` |
| Icons | `public/icons/` |

**Install flow:**

- **Android/Chrome:** `beforeinstallprompt` is captured → shown in `#install-banner` popup (visit-gated, cooldown-respecting) **and** in `#home-install-card` on the home dashboard (always visible when installable, no gating)
- **iOS/Safari:** UA sniffing detects iOS → `#ios-install-banner` shows instructions; also triggered from `#home-install-card` via `@open-ios-install-instructions.window` Alpine event

**iOS partial:** `resources/views/partials/ios-install-instructions.blade.php` — shared between the banner and any other surface.

### Web Push

| Setting | Value |
|---|---|
| Protocol | Web Push (VAPID) |
| Library | `minishlink/web-push` |
| Keys | `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` |
| Storage | `push_subscriptions` table |
| Subscribe | `POST /push/subscribe` |
| Unsubscribe | `DELETE /push/subscribe` |

`PushNotificationService` iterates subscriptions, sends via `minishlink/web-push`, and auto-removes expired subscriptions on 410 responses.

---

## 18. File Storage (Cloudflare R2)

All user-uploaded files go to Cloudflare R2 (S3-compatible). Configured as the `r2` filesystem disk.

**Direct-to-R2 uploads:**  
`LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=r2` causes Livewire to generate presigned PUT URLs — the browser uploads directly to R2, bypassing PHP. The CSP `connect-src` directive in `SecurityHeaders` includes `https://*.r2.cloudflarestorage.com`.

**R2-targeting FileUpload fields:**

| Resource | Field | Directory |
|---|---|---|
| Events | `cover_image_path` | `events/covers` |
| Souq Listings | `logo_path` | `souq/logos` |
| Badges | `icon_path` | `badges/icons` |
| Community Spaces | `cover_image_path` | `community/covers` |
| Resources | `file_path` | `resources/files` |
| Resources | `thumbnail_path` | `resources/thumbnails` |

**Automated setup** (`php artisan r2:setup`, runs on every deploy):
1. CORS policy on the R2 bucket (allows PUT from `APP_URL`)
2. Lifecycle rule: auto-delete `livewire-tmp/` objects after 24 hours

---

## 19. Email — Brevo HTTPS API

**Why Brevo?** SMTP ports (25, 465, 587) are blocked at the network level on Railway's infrastructure.

**Configuration:**
```env
MAIL_MAILER=brevo
BREVO_API_KEY=re_...
MAIL_FROM_ADDRESS=info@themuhsinatclub.com
```

**Custom transport:** `AppServiceProvider::registerBrevoMailTransport()` bridges Symfony's `BrevoTransportFactory` into `Mail::extend('brevo', ...)` using the `brevo+api` DSN scheme.

**Required packages:** `symfony/brevo-mailer` + `symfony/http-client` (both in `composer.json`).

> **Caution:** Do NOT add `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` — these are SMTP-only and will cause silent mail failures.

---

## 20. Security

### Global Middleware

**`SecurityHeaders`** (appended globally in `bootstrap/app.php`):
- `Content-Security-Policy` — restricts scripts, styles, connect-src, frame-src
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` — disables camera, microphone, geolocation

**`TrustRealIpHeader`** (global): Reads real client IP from Railway's `X-Real-IP` header. `X-Forwarded-*` headers are NOT trusted by default.

### Authorization Policies

| Policy | Protects |
|---|---|
| `JournalEntryPolicy` | Journal entries — users can only access their own |
| `MemberProfilePolicy` | Profile data |
| `EventPolicy` | Event management |
| `SouqListingPolicy` | Souq listing management |

### Data Privacy

- `JournalEntry.body` — `encrypted` cast; decrypted transparently by Eloquent using `APP_KEY`
- Passwords — `hashed` cast (bcrypt)
- Emails — always lowercase; normalised at boot and on every save

### Paystack Webhook Security

`PaystackWebhookController` verifies the `X-Paystack-Signature` HMAC-SHA512 header before processing any event.

### CSRF

Enforced globally via Laravel's default middleware. Explicitly excluded: `POST /webhooks/paystack` (HMAC-verified instead).

---

## 21. Database Schema

**Production:** Railway PostgreSQL  
**Local / Tests:** SQLite (in-memory for tests)

### Tables

```
users                         Auth, status, referral_code, member_id
member_profiles               Membership data, onboarding_status FSM
payment_records               Payment history with idempotency
membership_serials            Serial counter per Hijri year + membership type
audit_logs                    Immutable admin action log

events                        Halaqahs, webinars, meetups
event_rsvps                   Member RSVPs

resources                     Islamic resource library items
categories                    Content categories

journal_entries               Encrypted personal journals
dua_list_items                Personal du'a tracker

souq_listings                 Business directory entries
subscriptions                 Souq listing billing periods
subscription_plans            Billing plan definitions

jannah_coins_ledger           Append-only coins transaction log
user_badges                   Awarded badges (unique per user+badge)
user_referrals                Referral chain + reward records
user_interests                Pivot: user ↔ interest
user_goals                    Pivot: user ↔ goal
user_role_history             Role change audit trail

in_app_announcements          Homepage banners
dismissed_announcements       Pivot: user ↔ dismissed banner
broadcasts                    Push notification broadcasts
newsletters                   Email campaigns

community_spaces              Community circles/groups
support_applications          Zakat/Sadaqah support requests

settings                      Key-value configuration
push_subscriptions            Web Push VAPID endpoints

jobs, failed_jobs, job_batches  Laravel queue tables
sessions, cache, cache_locks  Session/cache storage
notifications                 Laravel database notification queue
passkeys                      Passkey auth storage
```

---

## 22. Testing

### Running Tests

```bash
# Full suite
php artisan test

# Focused regressions
php artisan test --filter AuthOnboardingTest
php artisan test --filter "PaymentRecordTest|PaystackWebhookTest|MembershipBillingTest"
php artisan test --filter HomeInstallButtonTest
```

### Test Configuration

- `phpunit.xml` uses **in-memory SQLite** — no PostgreSQL required
- `QUEUE_CONNECTION=sync` — jobs execute synchronously in tests
- `SESSION_DRIVER=array`
- `tests/bootstrap.php` auto-generates a minimal `.env` with `APP_KEY` on clean checkout

### Creating Test Members

```php
$this->seed(RoleSeeder::class);

$user = User::factory()->create([
    'status'        => 'active',
    'referral_code' => 'MEMB0001',
]);
$user->assignRole('member');
$user->memberProfile()->updateOrCreate(
    ['user_id' => $user->id],
    [
        'display_name'           => $user->name,
        'onboarding_status'      => 'member',
        'onboarding_completed_at' => now(),
        'current_period_ends_at' => now()->addDays(30),
    ]
);
```

> **Note:** There is no `MemberProfileFactory` — always use `updateOrCreate` on the relation.

### Key Test Files

| File | Covers |
|---|---|
| `AuthOnboardingTest.php` | Registration → onboarding → referral → `/home` |
| `PaymentRecordTest.php` | PaymentRecord creation and idempotency |
| `PaystackWebhookTest.php` | Webhook HMAC + payment state transition |
| `MembershipBillingTest.php` | Full billing lifecycle |
| `HomeInstallButtonTest.php` | PWA install card markup, global JS functions, deduplication |
| `SecurityHeadersCspTest.php` | CSP header contents |
| `FileUploadDiskConfigTest.php` | R2 disk config and FileUpload field targeting |

### E2E (Playwright)

```bash
php artisan db:seed --class=PlaywrightSeeder   # Seed test user
npm run test:e2e                                # Run (requires app at :8000)
```

Login: `member@test.com` / `password`  
Auth state: `tests/e2e/.auth/member.json`

---

## 23. Local Development

### Prerequisites

- PHP 8.4+
- Composer
- Node.js / npm
- SQLite

### Setup

```bash
# 1. Install dependencies
composer install && npm install

# 2. Copy and edit env
cp .env.example .env
# Edit DB_DATABASE to the absolute path of your local .sqlite file

# 3. Generate app key
php artisan key:generate

# 4. Migrate and seed
php artisan migrate --seed

# 5. Link storage
php artisan storage:link

# 6. Start everything
composer dev
```

`composer dev` starts the PHP dev server, queue worker, Vite, and log watcher concurrently.

### Local Admin Credentials

- **URL:** `http://127.0.0.1:8000/admin`
- **Email:** `admin@themuhsinatclub.com`
- **Password:** `Change1234!`

### Useful Commands

```bash
./vendor/bin/pint              # Format PHP (run before committing)
php artisan test               # Run test suite
php artisan migrate:fresh --seed  # Reset database
php artisan tinker             # Interactive REPL
```

---

## 24. Deployment (Railway)

### Services Required

| Service | How it runs |
|---|---|
| **Web** | Auto-detected by Nixpacks (PHP-FPM + Nginx, doc root `public/`) |
| **Worker** | `Procfile`: `php artisan queue:work --queue=default,membership,billing --sleep=3 --tries=3 --timeout=600` |
| **Scheduler** | Railway cron service: `php artisan schedule:run` every minute (`* * * * *`) |
| **Database** | Railway PostgreSQL plugin |
| **Storage** | Cloudflare R2 (S3-compatible, manual setup) |

### Build Command (`railway.json`)

```bash
npm ci && npm run build && php artisan storage:link && php artisan optimize && php artisan r2:setup
```

### PHP Version

Pinned in **two places** (must stay in sync):

| File | Setting |
|---|---|
| `nixpacks.toml` | `[phases.setup] nixPkgs = ["php84"]` |
| `composer.json` | `"php": "^8.4"` |

### First Deploy Sequence

Run via `railway run "..."` in order:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
php artisan optimize:clear
php artisan permission:cache-reset
```

### Remote Operations

```bash
railway run "php artisan migrate --force"    # Migrations
railway run "php artisan tinker"             # Production REPL
railway logs                                 # Web logs
railway logs --service worker                # Worker logs
```

### Queue Health Runbook

If emails or notifications are delayed:

```bash
# 1. Check worker logs
railway logs --service worker

# 2. Count pending jobs
railway run "php artisan tinker --execute=\"echo DB::table('jobs')->count();\""

# 3. Count failed jobs
railway run "php artisan tinker --execute=\"echo DB::table('failed_jobs')->count();\""

# 4. Run health sweep manually
railway run "php artisan queue:health-sweep"

# 5. Restart worker if down
railway service restart worker
```

### Vendor Corruption Recovery

If the app boots to a white screen with a PHP `ParseError` from `vendor/`:

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader
php artisan optimize:clear
```

---

## 25. Environment Variables

| Variable | Required | Notes |
|---|---|---|
| `APP_KEY` | ✅ | Laravel encryption key |
| `APP_ENV` | ✅ | `local` or `production` |
| `APP_URL` | ✅ | Full URL including scheme |
| `APP_TIMEZONE` | | Default `UTC` |
| `DB_CONNECTION` | ✅ | `pgsql` (prod) or `sqlite` (local) |
| `DB_DATABASE` | ✅ | Absolute path (SQLite) or DB name (PostgreSQL) |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | prod | PostgreSQL connection details |
| `BREVO_API_KEY` | ✅ | Brevo HTTPS mail API key |
| `MAIL_FROM_ADDRESS` | ✅ | Must be verified domain in Brevo |
| `MAIL_FROM_NAME` | | Display name for sent emails |
| `PAYSTACK_SECRET_KEY` | ✅ | Paystack secret key |
| `PAYSTACK_PUBLIC_KEY` | ✅ | Paystack public key (frontend) |
| `PAYSTACK_WEBHOOK_SECRET` | ✅ | HMAC signature verification |
| `PAYSTACK_PAYMENT_URL` | | Default: `https://api.paystack.co` |
| `VAPID_PUBLIC_KEY` | ✅ | Web Push VAPID public key |
| `VAPID_PRIVATE_KEY` | ✅ | Web Push VAPID private key |
| `VAPID_SUBJECT` | ✅ | `mailto:` contact for push service |
| `AWS_ACCESS_KEY_ID` | ✅ | Cloudflare R2 access key ID |
| `AWS_SECRET_ACCESS_KEY` | ✅ | Cloudflare R2 secret key |
| `AWS_BUCKET` | ✅ | R2 bucket name |
| `AWS_ENDPOINT` | ✅ | R2 S3-compatible endpoint URL |
| `AWS_URL` | ✅ | R2 public CDN URL (`pub-*.r2.dev`) |
| `AWS_DEFAULT_REGION` | ✅ | `auto` for Cloudflare R2 |
| `AWS_USE_PATH_STYLE_ENDPOINT` | ✅ | `true` for Cloudflare R2 |
| `FILESYSTEM_DISK` | prod | `r2` |
| `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` | prod | `r2` |
| `CLOUDFLARE_API_TOKEN` | prod | For `r2:setup` CORS configuration |
| `CLOUDFLARE_ACCOUNT_ID` | prod | For `r2:setup` CORS configuration |
| `SENTRY_LARAVEL_DSN` | prod | Sentry error tracking DSN |
| `QUEUE_CONNECTION` | prod | `database` |
| `SESSION_DRIVER` | prod | `database` |
| `CACHE_STORE` | prod | `database` |
| `TRUSTED_PROXIES` | optional | Comma-separated CIDRs for `X-Forwarded-*` trust |

---

*Documentation written October 2026. For deployment ops, brand constraints, and test commands, the canonical reference is `AGENTS.md` in the project root.*
