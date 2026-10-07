# TMC Application — Full Audit Report

**Date:** July 2, 2026  
**Framework:** Laravel 11.54 / PHP 8.5  
**Tests:** 269 passing (620 assertions)  

---

## 1. Application Overview

Three-surface app:

| Surface | URL | Tech | Auth |
|---|---|---|---|
| Public landing | `/` | Inline Blade (`resources/views/landing.blade.php`) | None |
| Member app | `/home`, `/events`, etc. | Livewire + Alpine.js | Fortify |
| Admin panel | `/admin` | Filament 3 | Fortify |

Auth via Fortify — no registration feature. Signup handled by `MembershipSignupWizard` Livewire component. Custom `FortifyLoginResponse` routes users by role and onboarding status. Custom `FortifyRegisterResponse` redirects to home.

---

## 2. Architecture

### 2.1 Models — 27

| Model | Table | Key Features |
|---|---|---|
| `User` | `users` | HasRoles (Spatie), Notifiable, FilamentUser, custom `sendPasswordResetNotification()` |
| `MemberProfile` | `member_profiles` | 50+ columns: identity, location, billing, payment, workflow, 19 timestamps |
| `Event` | `events` | Status machine (draft/published/cancelled/completed), RSVPs, coin_reward |
| `SouqListing` | `souq_listings` | 7 categories, 6 statuses, billing lifecycle, paystack_reference |
| `Subscription` | `subscriptions` | Generic subscription with Hijri-aware durations, type column (monthly/quarterly/annual) |
| `JannahCoinsLedger` | `jannah_coins_ledger` | Append-only immutable ledger (no UPDATED_AT) |
| `JournalEntry` | `journal_entries` | Encrypted body cast, soft-deletes |
| `AuditLog` | `audit_logs` | Append-only (no UPDATED_AT), old/new JSON diff, IP/user-agent tracking |
| `Badge` | `badges` | coin_reward, icon, criteria |
| `UserBadge` | `user_badges` | awarded_at, awarded_by |
| `UserReferral` | `user_referrals` | referrer_id, referred_id, coins_awarded flag |
| `MembershipSerial` | `membership_serials` | Counter table for membership ID generation |
| `UserRoleHistory` | `user_role_history` | History of role changes (old_role, new_role, reason) |
| `Broadcast` | `broadcasts` | Push notification campaigns |
| `Newsletter` | `newsletters` | Email campaigns |
| `InAppAnnouncement` | `in_app_announcements` | Dismissible popup announcements |
| `DismissedAnnouncement` | `dismissed_announcements` | Pivot for announcement dismissal tracking |
| `PushSubscription` | `push_subscriptions` | WebPush endpoint/auth storage |
| `Resource` | `resources` | Library resources with category |
| `Category` | `categories` | Resource categories |
| `DuaListItem` | `dua_list_items` | Saved dua items with soft-deletes |
| `CommunitySpace` | `community_spaces` | Youth/adult community spaces |
| `SupportApplication` | `support_applications` | Volunteer/partner applications |
| `Interest` | `interests` | Member interests (belongsToMany User) |
| `Goal` | `goals` | Member goals (belongsToMany User) |
| `Setting` | `settings` | Key-value settings store with typed registry |
| `EventRsvp` | `event_rsvps` | RSVP tracking (no timestamps, active scope) |

### 2.2 Services — 16

| Service | Responsibility |
|---|---|
| `MembershipStateService` | Membership lifecycle state machine: registered→onboarding→active→member→suspended |
| `BusinessStateService` | Souq listing state machine: pending→approved_unpaid→active→suspended |
| `SubscriptionStateService` | Subscription lifecycle: activate, expire, suspend, renew with Hijri-aware durations |
| `PaystackService` | Paystack API: initializePayment, verifyPayment, getAuthorizationUrl, initializeSouqListingPayment |
| `CoinsService` | Jannah Coins: award, deduct, getBalance, calculateMaxDiscount, applyRedemption, getHistory |
| `MembershipSignupService` | Full signup: create User + MemberProfile + role + membership ID + sync interests/goals |
| `MembersImportService` | CSV import: create users/profiles from CSV, send password reset emails, parse Hijri dates |
| `RsvpService` | Event RSVP: rsvp (coin reward for coin_reward>0), cancel, isRsvpd |
| `AuditLogService` | Centralized audit trail: log(action, model, old, new, actor, targetUserId) |
| `HijriDateService` | Hijri calendar: currentYear, formatHijriDate, addMonthsHijri, convertToGregorian |
| `MembershipIdService` | Generate TMC-{type}-{hijriYear}-{serial} IDs with DB-locked serial counters |
| `MembershipApprovalService` | Facade over MembershipStateService for approve/reject/needsCorrection |
| `NotificationService` | Orchestrate: admin notifications, applicant notifications, announcements, broadcasts |
| `ImageProcessingService` | Resize (max 800px) and store uploaded images via Intervention Image |
| `PushNotificationService` | WebPush (VAPID): send to single/many users, auto-delete expired subscriptions |
| `DuaListService` | Save/remove dua items with soft-delete restore |

### 2.3 Livewire Components — 26

| Area | Component | Purpose |
|---|---|---|
| **Membership** | `MembershipSignupWizard` | 5-step registration: personal → location → social → interests → summary |
| | `PaymentPage` | Billing cycle selector, coin redemption, Paystack redirect, payment polling |
| | `PendingReview` | Unreachable — auto-redirects to /home |
| **Community** | `CommunityHome` | Active community spaces listing |
| | `SpaceDetail` | Single space detail with events/resources |
| | `SupportForm` | Volunteer/partner application submission |
| **Souq** | `SouqDirectory` | Paginated, filterable business directory |
| | `ListingDetail` | Single listing detail |
| | `ApplyForm` | Listing creation + payment for approved_unpaid listings |
| **Events** | `EventsList` | Upcoming/past events with RSVP toggle |
| | `EventDetail` | Single event with RSVP button + coin reward info |
| **Resources** | `ResourcesLibrary` | Filterable library by category |
| | `ResourceDetail` | Single resource with save-to-dua option |
| **Profile** | `ProfileScreen` | Tabbed profile: overview, wallet, referrals, badges |
| | `EditProfile` | Edit form with interests/goals sync |
| | `NotificationPreferences` | Toggle: events, newsletters, push |
| | `LegacyCard` | Membership card with QR/info |
| **Home** | `HomeDashboard` | Greeting, coins, upcoming events, resources |
| **Journal** | `JournalScreen` | Encrypted journal with mood tracking |
| **Wallet** | `WalletScreen` | Coins display: balance, redemption calculator, history |
| **Notifications** | `Bell` | Unread count bell in header |
| **Layout** | `BottomNav` | Mobile bottom navigation |
| | `AnnouncementPopup` | Dismissible in-app announcement modal |

### 2.4 Middleware

| Middleware | Alias | Action |
|---|---|---|
| `EnsureUserStateRedirect` | `ensure.user.state` | Routes by status: admin/super_admin bypass, suspended→logout, registered/onboarding→signup, active/member→allow |
| `EnsureNotSuspendedFromRestrictedAreas` | `not-suspended` | Redirects suspended members to wallet with error message |

### 2.5 Route Structure

```
Public:
  GET  /                        → landing
  GET  /membership/signup       → SignupWizard (no auth)
  POST /webhooks/paystack       → PaystackWebhookController (CSRF-exempt)

Auth only:
  /membership/pending           → PendingReview
  /membership/payment           → PaymentPage
  /wallet                       → redirect to /profile?tab=wallet
  /push/subscribe               → PushSubscriptionController

Auth + ensure.user.state:
  /home, /events, /resources, /community, /profile, /journal*, /souq*
  (* also requires not-suspended)
```

### 2.6 Filament Admin

**15 Resources:**

| Navigation Group | Resources |
|---|---|
| Members | `UserResource` |
| Approvals | `MembershipApplicationResource`, `SupportApplicationResource` |
| Commerce | `SouqListingResource` |
| Content | `EventResource`, `ResourceResource`, `CommunitySpaceResource`, `BadgeResource`, `CategoryResource` |
| Communications | `InAppAnnouncementResource`, `BroadcastResource`, `NewsletterResource` |
| Configuration | `InterestResource`, `GoalResource` |
| Governance | `AuditLogResource` |

**3 Pages:** Dashboard, ManagePayments, SettingsPage  
**4 Widgets:** StatsOverview, PendingApplications, LatestApplications, RecentActivity

**Role-based Admin Access:**

| Role | Access |
|---|---|
| `super_admin` | Everything: all resources, audit logs, settings, role changes |
| `admin` | All except audit logs, settings, role changes |
| `moderator` | Users, membership apps, payments, newsletters, announcements |
| `content_editor` | Events, resources, categories, community spaces |
| `volunteer`, `member` | No admin access |

---

## 3. Payment System

### 3.1 Membership Payment Flow

```
Signup Wizard → status='active', payment_status='free'
  ↓ Free user visits /membership/payment
  ↓ Selects billing cycle (monthly/quarterly/yearly)
  ↓ Optionally applies Jannah Coins discount
  ↓ Redirects to Paystack checkout
  ↓ Pays on Paystack → returns to /membership/payment
  ↓ Polling every 5 seconds verifies payment
  ↓ status='member' → redirect to /home

  OR: Paystack webhook (POST /webhooks/paystack)
  → HMAC-SHA512 signature verification
  → charge.success → verifyPayment → recordPayment
  → status='member', coins awarded, notifications sent
```

**Billing details:**

| Item | Setting | Default |
|---|---|---|
| Monthly fee | `membership_fee_monthly` | ₦5,000 |
| Quarterly fee | `membership_fee_quarterly` | ₦12,000 |
| Yearly fee | `membership_fee_yearly` | ₦40,000 |
| Billing period | `membership_billing_cycle_days` | 30 days |
| Grace period | `membership_grace_period_days` | 7 days |
| Reminder before | `membership_reminder_days_before` | 7 days |

### 3.2 Souq Payment Flow

```
Submit listing → status='pending'
  ↓ Admin approves → status='approved_unpaid'
  ↓ BusinessApproved notification sent to user
  ↓ User visits /souq/apply → sees Pay button
  ↓ Pays listing fee via Paystack
  ↓ Webhook or polling activates → status='active'
  ↓ Billing starts (Hijri calendar, souq_billing_months duration)
```

**Souq settings:**

| Item | Setting | Default |
|---|---|---|
| Listing fee | `souq_listing_fee_kobo` | 500,000 kobo (₦5,000) |
| Billing period | `souq_billing_months` | 1 Hijri month |

### 3.3 Jannah Coins

| Setting | Default | Description |
|---|---|---|
| `coin_value_kobo` | 500 | Value per coin (₦5.00) |
| `max_redemption_percent` | 20 | Max % of payment via coins |
| `starter_coins_amount` | 50 | Welcome bonus |
| `referral_coins_amount` | 25 | Referral reward |
| `membership_approval_coins` | 100 | Admin approval reward |

**Earning sources:** Welcome, referral, event attendance, badge reward, admin manual award

**Redemption:** Up to 20% of any membership or Souq payment, coins deducted after payment confirmation via webhook

### 3.4 Paystack Configuration

| Key | Source |
|---|---|
| `PAYSTACK_PUBLIC_KEY` | `.env` |
| `PAYSTACK_SECRET_KEY` | `.env` |
| `PAYSTACK_WEBHOOK_SECRET` | `.env` |
| `PAYSTACK_SKIP_VERIFICATION` | `.env` (defaults to false) |

---

## 4. Membership State Machine

```
registered  → onboarding
onboarding  → active          (legacy/admin only)
active      → member, suspended
member      → suspended
suspended   → member, active
```

**Key flows:**

| Transition | When | Sets |
|---|---|---|
| → `onboarding` | `startOnboarding()` | `user.status = 'onboarding'` |
| → `active` | `MembershipSignupService::register()` | `payment_status = 'free'` |
| → `member` | `recordPayment()` (payment confirmed) | `payment_status = 'paid'`, periods set, `MembershipActivated` dispatched |
| → `suspended` | `checkGracePeriod()` (past grace) or `suspend()` | `user.status = 'suspended'` |
| → `active` | `reactivate()` | `user.status = 'active'`, timestamps reset |

---

## 5. Souq Listing State Machine

```
pending          → approved_unpaid, rejected
approved_unpaid  → active
active           → suspended
suspended        → active
rejected         → pending
```

---

## 6. Settings (Configurable via Admin)

### SettingsRegistry — 26 keys, 9 groups

| Group | Keys |
|---|---|
| Membership & Billing | `membership_fee_monthly`, `_quarterly`, `_yearly`, `membership_approval_coins`, `_billing_cycle_days`, `_grace_period_days`, `_reminder_days_before` |
| Souq | `souq_listing_fee_kobo`, `souq_billing_months` |
| Coins & Rewards | `referral_coins_amount`, `starter_coins_amount`, `coin_value_kobo`, `max_redemption_percent` |
| Notifications | `notify_renewal_reminders_enabled`, `_event_reminders_enabled`, `_souq_approval_enabled` |
| Donations | `bank_details`, `donate_message`, `suggested_donation_1/2/3` |
| Content | `support_banner_text` |
| Events | `event_reminder_hours_before` |
| Brand & Appearance | `brand_name`, `brand_primary_color` |
| Dashboard | `dashboard_active_window_days` |

---

## 7. Test Coverage

### 269 tests total — all pass, 0 skipped

| Category | Files | Tests |
|---|---|---|
| Subscription/Business Billing | `SubscriptionBillingTest` | 47 |
| Admin Settings | `AdminSettingsTest` | 15 |
| Souq/Wallet | `SouqWalletTest` | 15 |
| Push Notifications | `PushNotificationTest` | 15 |
| Paystack Webhooks | `PaystackWebhookTest` | 14 |
| Membership Billing | `MembershipBillingTest` | 12 |
| Admin Dashboard | `AdminDashboardTest` | 11 |
| Community/Profile | `CommunityProfileTest` | 10 |
| Password Reset | `PasswordResetTest` | 9 |
| Membership Applications | `MembershipApplicationTest` | 19 |
| Signup Service | `MembershipSignupServiceTest` | 8 |
| Signup Wizard | `MembershipSignupWizardTest` | 8 |
| Interests/Goals | `InterestGoalManagementTest` | 8 |
| Resources/Dua | `ResourcesTest` | 8 |
| State Redirect | `EnsureUserStateRedirectTest` | 7 |
| Journal Privacy | `JournalPrivacyTest` | 6 |
| Event RSVP | `EventRsvpTest` | 6 |
| Coin Redemption | `CoinRedemptionTest` | 16 |
| Auth/Onboarding | `AuthOnboardingTest` | 5 |
| Queue Reliability | `QueueReliabilityTest` | 5 |
| Email Idempotency | `EmailIdempotencyTest` | 4 |
| Receipt Download | `ReceiptDownloadTest` | 4 |
| Admin Payments | `AdminPaymentApprovalTest` | 4 |
| Legacy Card | `LegacyCardTest` | 3 |
| Souq Payment | `SouqPaymentTest` | 2 |
| Image Upload | `ImageUploadTest` | 2 |
| Health Check | `HealthCheckTest` | 2 |
| Payment Page | `PaymentPageRedirectTest` | 2 |
| Admin Events | `AdminEventResourceTest` | 1 |
| Unit | `ExampleTest` | 1 |

**Configuration:** SQLite in-memory, sync queue, array session, array mail  

**17 Playwright e2e tests:** 4 auth, 10 member page smoke, 2 mobile viewport, 1 souq payment redirect  

---

## 8. Database

### 57 migrations, 58+ tables

**Key tables:** `users`, `member_profiles`, `events`, `event_rsvps`, `souq_listings`, `subscriptions`, `jannah_coins_ledger`, `journal_entries`, `resources`, `categories`, `community_spaces`, `support_applications`, `badges`, `user_badges`, `user_referrals`, `membership_serials`, `interests`, `user_interests`, `goals`, `user_goals`, `settings`, `audit_logs`, `broadcasts`, `newsletters`, `in_app_announcements`, `dua_list_items`, `push_subscriptions`, `passkeys`, `notifications`, `user_role_history`, `dismissed_announcements`

### Seeders

| Seeder | Creates |
|---|---|
| `RoleSeeder` | 6 roles (super_admin, admin, moderator, content_editor, volunteer, member) |
| `AdminUserSeeder` | admin@themuhsinatclub.com / Change1234! |
| `InterestSeeder` | 10 interests |
| `GoalSeeder` | 4 goals |
| `EventSeeder` | 3 sample events (dev only) |
| `ResourceSeeder` | 4 sample resources (dev only) |
| `SouqSeeder` | 3 sample listings (dev only) |
| `CommunitySeeder` | 3 sample spaces (dev only) |
| `PlaywrightSeeder` | member@test.com / password (e2e only) |

---

## 9. Events, Listeners & Notifications

### Events (14)
`MembershipActivated`, `MembershipSubmitted`, `MembershipApproved`, `MembershipRejected`, `MembershipNeedsCorrection`, `SubscriptionActivated`, `SubscriptionExpired`, `SubscriptionExpiringSoon`, `SubscriptionSuspended`, `SubscriptionPaymentReceived`, `SubscriptionPaymentFailed`, `BusinessApproved`, `BusinessActivated`, `BusinessSuspended`

### Listeners (6)
`AwardWelcomeCoins`, `AwardReferralCoins`, `SendMembershipNotifications`, `SendBillingNotifications`, `LogMembershipEvent`, `LogBillingEvent`

### Notifications (19)
Membership: `MembershipApplicationSubmitted`, `MembershipUnderReviewNotification`, `MembershipApproved`, `MembershipRejected`, `MembershipNeedsCorrection`, `MembershipPaymentConfirmed`, `MembershipRenewalReminder`

Subscription: `SubscriptionActivated`, `SubscriptionExpired`, `SubscriptionExpiringSoon`, `SubscriptionSuspended`, `SubscriptionPaymentReceived`, `SubscriptionPaymentFailed`

Business: `BusinessApproved`, `BusinessActivated`, `BusinessSuspended`

Other: `BroadcastNotification`, `EventReminder`, `SetPasswordNotification`

---

## 10. Commands

| Command | Schedule | Action |
|---|---|---|
| `membership:check-grace-periods` | Daily | Suspends members past grace period |
| `membership:send-renewal-reminders` | Daily | Reminds members 7 days before expiry |

---

## 11. Completed Fixes & Features (This Session)

### Bug Fixes

| Bug | Change | Files |
|---|---|---|
| `EnsureUserStateRedirect` blocked `'member'` users from `/home` | Added `'member'` alongside `'active'` | `EnsureUserStateRedirect.php` |
| `FortifyLoginResponse` redirected `'member'` users to signup | Added `'member'` to status check | `FortifyLoginResponse.php` |
| `MembershipSignupWizard::mount()` didn't redirect `'member'` users | Added `'member'` to status check | `MembershipSignupWizard.php` |
| Webhook signature verified `json_encode($request->all())` instead of raw body | Changed to `$request->getContent()` | `PaystackWebhookController.php` |
| Souq payment stuck after Paystack redirect (no polling) | Added `checkPaymentStatus()` + `wire:poll.5s` | `ApplyForm.php`, `apply-form.blade.php` |
| Dead `/souq/payment` link in notification email | Changed to `route('souq.apply')` | `BusinessApproved.php` |
| Stale "reviewed by our team" copy in signup wizard | Removed review claim | `signup-wizard.blade.php` |
| `syncUserStatus()` in `MembershipStateService` missing `'member'` mapping | Noted but not critical — `recordPayment()` sets `user.status` directly | `MembershipStateService.php` |

### Features

| Feature | Files |
|---|---|
| **Moderator/content_editor live in main app** | |
| → Login goes to `/home` instead of `/admin` | `FortifyLoginResponse.php` |
| → `member` role retained alongside mod/editor role | `UserResource.php` |
| → Journal accessible to mod/content_editor | `JournalEntryPolicy.php`, `JournalScreen.php` |
| → "Admin" link in top bar for all admin roles | `app.blade.php` |
| → Null-safe memberProfile in notifications | `NotificationPreferences.php` |
| **Event coin rewards** | |
| → `coin_reward` column on events table | Migration `2026_07_01_164943` |
| → Coin reward field in Filament event form | `EventResource.php` |
| → RSVP awards coins with idempotency guard | `RsvpService.php` |
| **Brand settings** | |
| → `brand_name` and `brand_primary_color` configurable | `SettingsRegistry.php`, `AdminPanelProvider.php` |
| → Dashboard active window days configurable | `SettingsRegistry.php`, `StatsOverviewWidget.php` |
| **SubscriptionPlan removed** | |
| → `subscription_plans` table dropped, `type` column added to subscriptions | Migration `2026_07_01_182002` |
| → `SubscriptionPlan` model + seeder deleted | — |
| → All plan references replaced with `planName()` / `durationMonths()` | `Subscription.php`, `SubscriptionStateService.php`, notifications, listeners |
| **Admin user import** | |
| → CSV upload → creates users + profiles + sends password reset | `MembersImportService.php`, `SetPasswordNotification.php` |
| → Import action on Users list page | `ListUsers.php` |
| **Member type change (M/SM/E)** | |
| → "Change Member Type" action with membership ID regeneration | `UserResource.php`, `ViewUser.php` |

---

## 12. Known Issues & Remaining Tasks

### Open Issues

| Issue | Severity | Status |
|---|---|---|
| Coins deducted AFTER payment — if webhook fails, user gets discount but coins aren't deducted | Low | Monitoring |
| `pending-review.blade.php` (124 lines) is unreachable but contains stale "under review" copy | Cosmetic | Not urgent |
| Some email subjects/bodies hardcoded in Notifications — not configurable via Settings | Low | Future |
| No automated recurring billing — members must manually pay each billing cycle | By design | — |
| `MembershipStateService::syncUserStatus()` missing `'member'` from statusMap — works because `recordPayment()` sets `user.status` directly | Low | Fragile, fix in future |

### Production Deploy Checklist

| Item | Status |
|---|---|
| Connect GitHub repo to Railway | ⬜ |
| Add Railway PostgreSQL + Tigris plugins | ⬜ |
| Set all env vars in Railway dashboard (copy from `.env.example`) | ⬜ |
| Configure `FILESYSTEM_DISK=s3` and Tigris AWS vars | ⬜ |
| Swap `PAYSTACK_PUBLIC_KEY` to `pk_live_` | ⬜ |
| Swap `PAYSTACK_SECRET_KEY` to `sk_live_` | ⬜ |
| Set `PAYSTACK_WEBHOOK_SECRET` and match Paystack dashboard | ⬜ |
| Run `php artisan migrate --force` (via `railway run`) | ⬜ |
| Run seeders: RoleSeeder, AdminUserSeeder (via `railway run`) | ⬜ |
| Run `php artisan permission:cache-reset` (via `railway run`) | ⬜ |
| Full test suite: all passing | ✅ |
| Manual payment walkthrough (membership + souq) | ✅ |

---

## 13. File Index

### New files (this session)

```
app/Notifications/SetPasswordNotification.php
app/Services/MembersImportService.php
database/migrations/2026_07_01_164943_add_coin_reward_to_events_table.php
database/migrations/2026_07_01_182002_simplify_subscriptions_remove_plans.php
tests/Feature/EnsureUserStateRedirectTest.php
tests/Feature/SouqPaymentTest.php
tests/e2e/souq-payment.spec.js
```

### Deleted files (this session)

```
app/Models/SubscriptionPlan.php
database/seeders/SubscriptionPlanSeeder.php
```

### Modified files (this session — 30 files)

```
app/Filament/Resources/EventResource.php
app/Filament/Resources/UserResource.php
app/Filament/Resources/UserResource/Pages/ListUsers.php
app/Filament/Resources/UserResource/Pages/ViewUser.php
app/Filament/Widgets/StatsOverviewWidget.php
app/Http/Controllers/PaystackWebhookController.php
app/Http/Middleware/EnsureUserStateRedirect.php
app/Http/Responses/FortifyLoginResponse.php
app/Listeners/LogBillingEvent.php
app/Listeners/SendBillingNotifications.php
app/Livewire/Journal/JournalScreen.php
app/Livewire/Membership/MembershipSignupWizard.php
app/Livewire/Profile/NotificationPreferences.php
app/Livewire/Souq/ApplyForm.php
app/Models/Event.php
app/Models/Subscription.php
app/Models/User.php
app/Notifications/BusinessApproved.php
app/Notifications/SubscriptionActivated.php
app/Notifications/SubscriptionExpiringSoon.php
app/Policies/JournalEntryPolicy.php
app/Providers/Filament/AdminPanelProvider.php
app/Services/RsvpService.php
app/Services/SubscriptionStateService.php
app/Settings/SettingsRegistry.php
resources/views/layouts/app.blade.php
resources/views/livewire/membership/signup-wizard.blade.php
resources/views/livewire/souq/apply-form.blade.php
tests/Feature/PasswordResetTest.php
tests/Feature/SubscriptionBillingTest.php
```
