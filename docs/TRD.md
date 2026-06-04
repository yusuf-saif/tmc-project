# The Muhsinat Club (TMC) — Technical Requirements Document
**Version:** 2.0  
**Stack:** Laravel 11 · Livewire 3 · Tailwind CSS · MySQL 8 · Filament v3 · Spatie

---

## 1. Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Language | PHP | 8.2+ |
| Framework | Laravel | 11.x |
| Frontend | Blade + Livewire | 3.x |
| Interactivity | Alpine.js | 3.x |
| Styling | Tailwind CSS | 3.x |
| Database | MySQL | 8.0+ |
| Admin panel | Filament | 3.x |
| Auth | Laravel Fortify | — |
| Permissions | Spatie Laravel Permission | 6.x |
| Push notifications | minishlink/web-push | — |
| Queue driver | Database (dev) · Redis (prod) | — |
| File storage | Laravel Storage (local dev, S3 prod) | — |
| Rich text editor | TipTap (via Filament) | — |
| Testing | PHPUnit | — |
| Deployment | Nginx · Ubuntu 22.04 · Laravel Forge | — |

---

## 2. Repository Structure

Single GitHub repository. Landing page and app in the same repo.

```
/                          ← Laravel root = repo root
├── public/                ← web root (Nginx points here)
│   ├── css/style.css      ← landing page styles
│   ├── js/main.js         ← landing page scripts
│   ├── images/            ← brand assets
│   │   ├── img1.png       ← logo mark
│   │   ├── img2.png       ← full logo
│   │   ├── img3.png       ← Arabic calligraphy logo
│   │   └── img4.png       ← botanical pattern
│   ├── manifest.json      ← PWA manifest
│   └── sw.js              ← service worker
├── resources/
│   └── views/
│       ├── landing.blade.php   ← public landing page at /
│       ├── offline.blade.php   ← PWA offline fallback
│       ├── layouts/
│       │   └── app.blade.php   ← member app shell
│       └── livewire/           ← all Livewire component views
├── app/
│   ├── Livewire/           ← all Livewire component classes
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   │   ├── AuditLogService.php
│   │   ├── CoinsService.php
│   │   ├── RsvpService.php
│   │   ├── DuaListService.php
│   │   └── PushNotificationService.php
│   └── Filament/
│       └── Resources/
├── routes/
│   └── web.php
└── database/
    ├── migrations/
    └── seeders/
```

---

## 3. Routing Architecture

```php
// PUBLIC
GET  /                    → landing.blade.php (no auth)
GET  /offline             → offline.blade.php (no auth, cached by SW)

// AUTH (Fortify)
GET  /register
GET  /login
GET  /forgot-password
GET  /reset-password/{token}
GET  /verify-email
GET  /onboarding          → auth, NOT onboarding-checked

// MEMBER APP (auth + onboarding middleware)
GET  /home
GET  /events
GET  /events/{slug}
GET  /resources
GET  /resources/{slug}
GET  /journal
GET  /souq
GET  /souq/apply
GET  /souq/{slug}
GET  /wallet
GET  /community
GET  /community/spaces/{slug}
GET  /community/support/{type}  // volunteer|mentorship
GET  /community/donate
GET  /profile
GET  /profile/edit
GET  /profile/legacy-card
GET  /profile/notifications
GET  /notifications

// PUSH
POST   /push/subscribe
DELETE /push/subscribe

// ADMIN (Filament)
GET  /admin                → Filament panel
```

---

## 4. Database Schema

### users
```sql
id, name, email, email_verified_at, password,
status ENUM('active','suspended') DEFAULT 'active',
suspended_at, suspended_reason,
referral_code VARCHAR(8) UNIQUE,
referred_by BIGINT FK(users) NULLABLE,
remember_token, created_at, updated_at, deleted_at
```

### user_profiles
```sql
id, user_id BIGINT FK(users) UNIQUE,
display_name, avatar_path NULLABLE,
notification_preferences JSON,
goals JSON,
onboarding_completed_at TIMESTAMP NULLABLE,
created_at, updated_at
```

### interests
```sql
id, name, slug, icon NULLABLE, is_active BOOLEAN, sort_order INT
```

### user_interests *(pivot)*
```sql
user_id FK, interest_id FK
```

### goals
```sql
id, name, slug ENUM('community','learning','business','volunteering'), is_active
```

### user_goals *(pivot)*
```sql
user_id FK, goal_id FK
```

### jannah_coins_ledger
```sql
id, user_id FK,
type ENUM('earned','adjusted','deducted'),
reason ENUM('onboarding','referral','manual','admin_adjustment'),
amount INT,
reference_id BIGINT NULLABLE,
admin_note TEXT NULLABLE,
created_at
```

### user_referrals
```sql
id, referrer_id FK(users), referred_id FK(users) UNIQUE,
coins_awarded BOOLEAN DEFAULT false,
created_at
```

### events
```sql
id, title, slug UNIQUE, description LONGTEXT,
location_type ENUM('online','in_person','hybrid'),
location_detail TEXT NULLABLE,
event_date DATETIME, end_date DATETIME NULLABLE,
cover_image_path NULLABLE, external_link NULLABLE,
status ENUM('draft','published','cancelled','completed'),
created_by FK(users), updated_by FK(users) NULLABLE,
created_at, updated_at
```

### event_rsvps
```sql
id, event_id FK, user_id FK,
rsvp_at TIMESTAMP, cancelled_at TIMESTAMP NULLABLE
UNIQUE(event_id, user_id)
```

### resources
```sql
id, title, slug UNIQUE, description TEXT,
category ENUM('dua_book','dear_allah','pocket_guide','audio_halaqahs'),
type ENUM('article','dua','pdf','audio','video_link','guide'),
body LONGTEXT NULLABLE,
file_path NULLABLE, external_url NULLABLE,
thumbnail_path NULLABLE,
status ENUM('draft','published','archived'),
created_by FK, updated_by FK NULLABLE,
created_at, updated_at
```

### journal_entries
```sql
id, user_id FK,
entry_date DATE,
mood ENUM('happy','grateful','reflective','sad','anxious','neutral'),
body LONGTEXT  -- encrypted cast
created_at, updated_at, deleted_at
```

### dua_list_items
```sql
id, user_id FK,
resource_id FK(resources) NULLABLE,
dua_text TEXT,
label VARCHAR(100) NULLABLE,
created_at, updated_at, deleted_at
```

### souq_listings
```sql
id, user_id FK, business_name, slug UNIQUE,
category ENUM('fashion','food_catering','health_beauty',
              'education','services','creative','other'),
description TEXT,  -- max 300 chars enforced in validation
contact_email, phone NULLABLE, website NULLABLE, instagram NULLABLE,
logo_path NULLABLE,
status ENUM('pending','approved','rejected','archived'),
admin_note TEXT NULLABLE,
reviewed_by FK(users) NULLABLE, reviewed_at TIMESTAMP NULLABLE,
created_at, updated_at
```

### community_spaces
```sql
id, name, slug UNIQUE, short_description,
description LONGTEXT, guidelines LONGTEXT NULLABLE,
cover_image_path NULLABLE, external_link NULLABLE,
is_youth_space BOOLEAN DEFAULT false,
is_active BOOLEAN DEFAULT true,
sort_order INT DEFAULT 0,
created_at, updated_at
```

### announcements
```sql
id, title, slug UNIQUE, body TEXT,
status ENUM('draft','scheduled','published','archived'),
publish_at DATETIME NULLABLE, published_at DATETIME NULLABLE,
created_by FK, updated_by FK NULLABLE,
created_at, updated_at
```

### support_applications
```sql
id, user_id FK NULLABLE,
type ENUM('volunteer','mentorship'),
name, email, motivation TEXT, skills_or_focus TEXT,
availability TEXT NULLABLE,
status ENUM('pending','reviewed','accepted','declined'),
admin_notes TEXT NULLABLE,
reviewed_by FK(users) NULLABLE, reviewed_at TIMESTAMP NULLABLE,
created_at
```

### badges
```sql
id, name, description, icon_path, criteria TEXT, is_active
```

### user_badges
```sql
id, user_id FK, badge_id FK,
awarded_at TIMESTAMP, awarded_by FK(users) NULLABLE
```

### push_subscriptions
```sql
id, user_id FK,
endpoint TEXT UNIQUE,
public_key TEXT, auth_token TEXT,
created_at, updated_at
```

### notification_logs
```sql
id, type, title, body,
audience_type ENUM('all','interest','goal','individual'),
audience_value JSON NULLABLE,
sent_by FK(users), sent_at TIMESTAMP,
delivery_count INT DEFAULT 0,
created_at
```

### settings
```sql
id, key VARCHAR(100) UNIQUE, value TEXT,
description TEXT NULLABLE,
updated_by FK(users) NULLABLE,
updated_at
```

### audit_logs
```sql
id, user_id FK(users) NULLABLE,
action VARCHAR(100),
auditable_type VARCHAR(100) NULLABLE,
auditable_id BIGINT NULLABLE,
old_values JSON NULLABLE,
new_values JSON NULLABLE,
ip_address VARCHAR(45) NULLABLE,
user_agent TEXT NULLABLE,
created_at
-- NO updated_at — immutable
```

### user_role_history
```sql
id, user_id FK, changed_by FK(users),
old_role VARCHAR(50), new_role VARCHAR(50),
reason TEXT NULLABLE,
created_at
```

---

## 5. Services

### AuditLogService
```php
AuditLogService::log(
    action: string,
    model: ?Model = null,
    oldValues: array = [],
    newValues: array = []
): void
```
Writes to `audit_logs`. Always called after any admin mutation.

### CoinsService
```php
CoinsService::getBalance(User $user): int
CoinsService::award(User $user, int $amount, string $reason, ?int $referenceId, ?string $note): void
CoinsService::deduct(User $user, int $amount, string $reason, string $note): void
CoinsService::getHistory(User $user): LengthAwarePaginator
```
Balance is always computed as `SUM(amount)` from ledger. Never cached.

### RsvpService
```php
RsvpService::rsvp(User $user, Event $event): void
RsvpService::cancel(User $user, Event $event): void
RsvpService::isRsvpd(User $user, Event $event): bool
```
Dispatches `SendEventReminderNotification` job on new RSVP.

### DuaListService
```php
DuaListService::save(User $user, Resource $resource): void
DuaListService::saveManual(User $user, string $text, ?string $label): void
DuaListService::remove(User $user, DuaListItem $item): void
DuaListService::isSaved(User $user, Resource $resource): bool
```

### PushNotificationService
```php
PushNotificationService::send(User $user, string $title, string $body, ?string $url): void
PushNotificationService::sendToMany(Collection $users, string $title, string $body, ?string $url): void
```
Handles expired subscriptions gracefully (delete and continue).

---

## 6. Middleware

| Middleware | Class | Applied to |
|-----------|-------|-----------|
| auth | Laravel default | All member routes |
| verified | Laravel default | All member routes |
| onboarded | `EnsureOnboardingComplete` | All member routes except `/onboarding` |

### EnsureOnboardingComplete
```php
if (auth()->check() && !auth()->user()->profile?->onboarding_completed_at) {
    return redirect()->route('onboarding');
}
```

---

## 7. Policies

| Policy | Model | Rules |
|--------|-------|-------|
| `JournalEntryPolicy` | JournalEntry | Only owner. Admins explicitly denied. |
| `DuaListItemPolicy` | DuaListItem | Only owner. |
| `SouqListingPolicy` | SouqListing | Owner can view own. Admins manage all. |
| `EventRsvpPolicy` | EventRsvp | Only owner. |

**JournalEntryPolicy is the most critical.** Admin roles must be explicitly blocked, not just "not allowed". Test this:
```php
public function view(User $user, JournalEntry $entry): bool {
    return $user->id === $entry->user_id; // Only the owner. Full stop.
}
```

---

## 8. Queued Jobs

| Job | Trigger | Queue |
|-----|---------|-------|
| `SendEventReminderNotification` | On RSVP, fires 24h before event | `notifications` |
| `SouqApprovedNotification` | Admin approves listing | `notifications` |
| `BroadcastPushNotification` | Admin sends broadcast | `broadcasts` |
| `SendWelcomeEmail` | After onboarding complete | `emails` |

---

## 9. Scheduled Tasks

```php
// Publish scheduled announcements
Schedule::command('announcements:publish')->everyMinute();

// Daily backup
Schedule::command('backup:run')->dailyAt('02:00');

// Clean expired password reset tokens
Schedule::command('auth:clear-resets')->daily();
```

---

## 10. Security Requirements

| Requirement | Implementation |
|------------|---------------|
| CSRF protection | Laravel default on all forms |
| XSS prevention | Always `{{ }}` never `{!! !!}` for user content |
| SQL injection | Eloquent ORM and Query Builder only |
| File upload validation | MIME type check, not extension only |
| Rate limiting | 5 login attempts per minute (Fortify) |
| Journal encryption | `encrypted` cast on `body` field |
| Journal access | JournalEntryPolicy, tested in CI |
| HTTPS | Required in production (service worker requires it) |
| Admin access | `canAccessPanel()` on User model |
| Role protection | Spatie policies + Filament gates |

---

## 11. PWA Requirements

### manifest.json
```json
{
  "name": "The Muhsinat Club",
  "short_name": "TMC",
  "start_url": "/home",
  "display": "standalone",
  "background_color": "#FAF8F3",
  "theme_color": "#1A6B72",
  "icons": [
    { "src": "/images/img1.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/images/img1.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

### Service Worker Cache List
- `/`
- `/home`
- `/offline`
- `/css/style.css`
- `/js/app.js`
- `/manifest.json`
- `/images/img1.png`

---

## 12. Performance Requirements

### Database indexes required
```sql
ALTER TABLE events ADD INDEX idx_events_status_date (status, event_date);
ALTER TABLE souq_listings ADD INDEX idx_souq_status (status);
ALTER TABLE jannah_coins_ledger ADD INDEX idx_coins_user (user_id);
ALTER TABLE journal_entries ADD INDEX idx_journal_user (user_id, deleted_at);
ALTER TABLE member_notifications ADD INDEX idx_notif_user (user_id, read_at);
```

### Eager loading required
- `events` with `creator`
- `souq_listings` with `user`
- `resources` with no N+1 on category lists
- `journal_entries` always scoped to `auth()->id()`

### Image processing
- All uploaded images resized to max 800px width on upload
- Use `intervention/image` package

---

## 13. Testing Requirements

Every phase must pass `php artisan test` before the next phase starts.

### Critical test cases (must exist)
```
✓ Member can register, verify email, complete onboarding
✓ 50 coins awarded exactly once on onboarding
✓ Referral coins awarded after referred member verifies
✓ Unboarded member redirected to /onboarding
✓ Member can RSVP and cancel RSVP
✓ Duplicate RSVP prevented
✓ Journal entry only accessible by owner
✓ Admin cannot access journal body via any route
✓ Journal body stored encrypted in database
✓ Souq listing only visible after approval
✓ Role change logged in user_role_history
✓ Role change logged in audit_logs
✓ Push subscription saves and deletes correctly
✓ PushNotificationService handles missing subscription gracefully
✓ Landing page returns 200 at GET /
```

---

## 14. Deployment Checklist

```bash
# Server
Ubuntu 22.04, PHP 8.2, MySQL 8.0, Nginx
SSL via Let's Encrypt (required for PWA + push)

# App
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan optimize
npm run build

# Background workers (Supervisor)
php artisan queue:work --queue=notifications,broadcasts,emails,default

# Cron
* * * * * cd /var/www/tmc && php artisan schedule:run >> /dev/null 2>&1

# Backups (Spatie Backup)
php artisan backup:run (scheduled daily at 2am, stored off-server)
```
