# The Muhsinat Club (TMC) — Build Phases Prompt Guide
**For:** Claude Code, Cursor, Windsurf  
**Usage:** Paste the stack context + the relevant phase prompt at the start of each session

---

## Stack Context Block
**Paste this at the top of EVERY coding session before the phase prompt.**

```
--- TMC STACK CONTEXT ---
App:          The Muhsinat Club — faith-based community PWA for Muslim women
Framework:    Laravel 11 (PHP 8.2+)
Frontend:     Blade + Livewire 3 + Alpine.js
Styling:      Tailwind CSS v3
Database:     MySQL 8.0+
Admin:        Filament v3 (panel at /admin)
Auth:         Laravel Fortify
Permissions:  Spatie Laravel Permission
Queue:        database driver (dev), Redis (prod)
Storage:      Laravel Storage local (dev), S3 (prod)
Tests:        PHPUnit

Brand colours:
  --teal: #1A6B72  --teal-dk: #0D3F44  --gold: #C8A84B
  --plum: #3D1A47  --ivory: #FAF8F3    --ink: #1C1A17

Fonts:
  Dancing Script  → all headings, brand name, display text
  Nunito          → all body copy, UI labels, buttons
  Amiri           → Arabic text ONLY (direction: rtl always)

Brand assets in /public/images/:
  img1.png → logo mark (nav, favicon, legacy card)
  img2.png → full logo (footer, onboarding)
  img3.png → Arabic calligraphy logo (section divider)
  img4.png → botanical pattern (section bg texture)

Rules:
  - Landing page at / must never break
  - Journal body is encrypted (encrypted cast), admin cannot read it
  - All permissions enforced server-side via Policies
  - All admin actions logged to audit_logs
  - php artisan test must pass before moving to next phase
  - git commit at end of every phase
-------------------------
```

---

## Rules for Every Phase

1. Always paste the stack context block first
2. Complete one phase fully before starting the next
3. Run `php artisan test` — all must pass before moving on
4. Run `git commit -m "Phase X complete"` before moving on
5. The landing page at `/` must be pixel-perfect after every phase
6. Journal content is private — no exceptions, no workarounds

---

## Phase 0 — Foundation & Setup

```
You are setting up the TMC Laravel application.
This is Phase 0 — Foundation. No member features yet.
The repo currently has a live landing page (index.html + css/ + js/ + images/).

Complete these tasks in order:

TASK 1 — Install Laravel into the current directory
  composer create-project laravel/laravel . --prefer-dist
  Confirm when prompted about existing files.

TASK 2 — Move landing page assets into Laravel
  css/style.css  → public/css/style.css
  js/main.js     → public/js/main.js
  images/        → public/images/
  Delete root-level css/, js/, images/ folders.
  Delete index.html from root.

TASK 3 — Convert landing page to Blade
  Create resources/views/landing.blade.php
  Copy the full contents of index.html into it.
  Update asset references:
    href="css/style.css"   → href="{{ asset('css/style.css') }}"
    src="js/main.js"       → src="{{ asset('js/main.js') }}"
    src="images/img1.png"  → src="{{ asset('images/img1.png') }}"
    (same for img2, img3, img4)
    href="images/img1.png" → href="{{ asset('images/img1.png') }}" (favicon)
  Do NOT change any copy, class names, or HTML structure.

TASK 4 — Configure .env
  APP_NAME="The Muhsinat Club"
  APP_URL=http://localhost
  DB_DATABASE=tmc_app
  DB_USERNAME=root
  DB_PASSWORD=
  QUEUE_CONNECTION=database
  SESSION_DRIVER=database
  MAIL_MAILER=log

TASK 5 — Set up routes/web.php
  Route::get('/', fn() => view('landing'))->name('landing');
  Route::get('/offline', fn() => view('offline'))->name('offline');

TASK 6 — Run migrations
  Create database tmc_app in MySQL.
  php artisan migrate

TASK 7 — Install Filament
  composer require filament/filament:"^3.0" -W
  php artisan filament:install --panels
  (panel id: admin, path: admin)
  In AdminPanelProvider:
    path('admin')
    brandName('The Muhsinat Club')
    brandLogo(asset('images/img2.png'))
    favicon(asset('images/img1.png'))
    colors(['primary' => Color::hex('#1A6B72')])

TASK 8 — Install Spatie Laravel Permission
  composer require spatie/laravel-permission
  php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
  php artisan migrate

TASK 9 — Add migrations for settings and audit_logs
  settings: id, key (VARCHAR 100 UNIQUE), value TEXT,
    description TEXT NULL, updated_by FK NULL, updated_at
  audit_logs: id, user_id FK NULL, action VARCHAR(100),
    auditable_type VARCHAR(100) NULL, auditable_id BIGINT NULL,
    old_values JSON NULL, new_values JSON NULL,
    ip_address VARCHAR(45) NULL, user_agent TEXT NULL,
    created_at ONLY (no updated_at — immutable)

TASK 10 — Create AuditLogService
  app/Services/AuditLogService.php
  public static function log(string $action, ?Model $model = null,
    array $old = [], array $new = []): void
  Writes to audit_logs with auth()->id(), request()->ip(), request()->userAgent()

TASK 11 — Seed roles
  RoleSeeder: create Spatie roles:
    super_admin | admin | moderator | content_editor | volunteer | member
  AdminUserSeeder: create user
    name: TMC Admin, email: admin@themuhsinatclub.com
    password: hashed 'Change1234!'
    role: super_admin
  Add HasRoles trait to User model
  Add canAccessPanel() to User model — allow roles:
    super_admin, admin, moderator, content_editor
  Run: php artisan db:seed

TASK 12 — Verify
  php artisan serve
  GET / → landing page (identical to before)
  GET /admin → Filament login
  Login as admin → dashboard loads
  php artisan test → all pass
```

---

## Phase 1 — Authentication & Onboarding

```
You are building Phase 1 — Authentication and Onboarding for TMC.
Phase 0 is complete. Laravel, Filament, and Spatie are installed.

MIGRATIONS:
  Add to users table:
    status ENUM('active','suspended') DEFAULT 'active'
    suspended_at TIMESTAMP NULL
    suspended_reason TEXT NULL
    referral_code VARCHAR(8) UNIQUE
    referred_by BIGINT UNSIGNED FK(users) NULL

  user_profiles:
    id, user_id FK UNIQUE, display_name,
    avatar_path NULL, notification_preferences JSON NULL,
    goals JSON NULL, onboarding_completed_at TIMESTAMP NULL,
    created_at, updated_at

  interests:
    id, name, slug, icon NULL, is_active BOOLEAN, sort_order INT

  user_interests: user_id FK, interest_id FK

  goals:
    id, name, slug ENUM('community','learning','business','volunteering'),
    is_active BOOLEAN

  user_goals: user_id FK, goal_id FK

  jannah_coins_ledger:
    id, user_id FK,
    type ENUM('earned','adjusted','deducted'),
    reason ENUM('onboarding','referral','manual','admin_adjustment'),
    amount INT, reference_id BIGINT NULL,
    admin_note TEXT NULL, created_at

  user_referrals:
    id, referrer_id FK(users), referred_id FK(users) UNIQUE,
    coins_awarded BOOLEAN DEFAULT false, created_at

SEEDERS:
  InterestSeeder: Qur'an, Du'a, Motherhood, Sisterhood, Business,
    Wellbeing, Marriage, Career, Volunteering, Education
  GoalSeeder: Community, Learning, Business, Volunteering

FORTIFY:
  Install and configure Laravel Fortify.
  Enable: registration, login, logout, email verification, password reset.
  After email verification → redirect to /onboarding
  After login:
    if onboarding_completed_at is null → /onboarding
    else → /home

AUTH VIEWS (Blade, TMC branded, mobile-first):
  All screens: ivory bg, img1 logo, Dancing Script headings, Nunito body
  /register — name, email, password, confirm password
  /login — email, password show/hide toggle, remember me, forgot password link
  /forgot-password — email input
  /reset-password/{token} — new password + confirm
  /verify-email — check email prompt with resend link

ONBOARDING (Livewire: App\Livewire\Onboarding\OnboardingWizard):
  Multi-step, progress bar showing "Step X of 4"
  Cannot skip forward. Back allowed.

  Step 1 — Interests:
    Chip-style multi-select, min 1 max 5
    Selected: teal bg white | Unselected: ivory bg border
    Live counter "X/5 selected"

  Step 2 — Goals:
    4 cards in 2x2 grid, icon + title + description
    Selected: teal-lt bg teal border | Unselected: white border

  Step 3 — Notification preferences:
    Toggle switches for: Events & Halaqahs | Announcements |
    Coins & Rewards | Community Updates
    Save as JSON to user_profiles.notification_preferences

  Step 4 — Welcome screen:
    img1 logo 60px centred
    Dancing Script: "Welcome to The Muhsinat Club"
    "You've earned 50 Jannah Coins"
    On render this step:
      Set user_profiles.onboarding_completed_at = now()
      Insert jannah_coins_ledger row: type=earned, reason=onboarding, amount=50
    CTA: .btn-gold "Enter the Club →" → redirect to /home

MIDDLEWARE — EnsureOnboardingComplete:
  If auth and onboarding_completed_at is null → redirect to /onboarding
  Apply to all member routes EXCEPT /onboarding itself
  Register in bootstrap/app.php

REFERRAL:
  On registration: generate unique 8-char referral_code for each user
  Accept ?ref=CODE in registration URL → set users.referred_by
  After referred user verifies email:
    Award 25 coins to referrer (reason=referral)
    Create user_referrals row (coins_awarded=true)
    Log to AuditLogService

FEATURE TESTS:
  Register → verify → complete 4 onboarding steps → redirected to /home
  50 coins in ledger, onboarding_completed_at set
  Coins awarded exactly once (re-visiting step 4 does not re-award)
  Referral coins awarded after referred user verifies email
  Unonboarded user redirected from /home to /onboarding
```

---

## Phase 2 — Home Dashboard

```
You are building Phase 2 — Home Dashboard for TMC.
Auth and onboarding from Phase 1 are complete.

LAYOUT SHELL (resources/views/layouts/app.blade.php):
  Mobile-first, ivory bg
  Top bar: img1 logo (28px) left + notification bell (Livewire) right
  Bottom padding pb-20 to clear bottom nav
  @vite, @livewireStyles, @livewireScripts

BOTTOM NAVIGATION (Livewire: App\Livewire\Layout\BottomNav):
  Fixed bottom bar, height 64px, white bg, border-top
  7 tabs: Home | Events | Souq | Community | Wallet | Journal | Profile
  Icons: Heroicons outline
  Active tab: var(--teal) text and icon
  Labels: Nunito 10px uppercase
  Current route detection for active state

HOME SCREEN (Livewire: App\Livewire\Home\HomeDashboard):
  Route: GET /home (auth + verified + onboarded middleware)

  Section A — Greeting card:
    Background: var(--teal), img4 pattern at 8% opacity via ::before
    "Assalamu Alaykum, [first_name]" — Dancing Script 1.8rem white
    Rotating phrase (daily, from array of 7 Islamic phrases)
    Border-radius 12px, padding 1.2rem

  Section B — Announcement banner:
    Fetch: latest announcement where status='published'
    If exists: gold bg, title Nunito 500 teal-dk, 100 char preview
    Tap: opens full announcement at /announcements/{slug}
    Hidden if none published

  Section C — Coins snapshot:
    Background: rgba(200,168,75,0.08), border rgba(200,168,75,0.22)
    ✦ icon (24px gold circle) + balance Dancing Script 1.4rem gold
    "Jannah Coins" label Nunito 11px uppercase ink-soft
    Links to /wallet

  Section D — Upcoming events:
    Fetch: published events where event_date >= now(), limit 3, ordered ASC
    Each card: title Nunito 500, date Nunito 300, location badge
    RSVP button → /events/{slug}
    Empty state if no events

  Section E — Quick actions:
    4 tiles: Journal | Du'a Book | Events | Souq
    White bg, border, radius 8px, 22px Heroicon, 10px label

  Section F — Support TMC banner:
    Soft teal-lt card, "Support our mission →" links to /community

ANNOUNCEMENTS MODEL:
  Create Announcement model + migration now (needed for home):
    id, title, slug, body TEXT,
    status ENUM('draft','scheduled','published','archived'),
    publish_at NULL, published_at NULL,
    created_by FK, updated_by FK NULL, timestamps

FEATURE TESTS:
  Authenticated onboarded user reaches /home
  Unauthenticated user redirected to /login
  Coins balance correct (50 from onboarding)
  Only published future events shown
  Announcement hidden when none published
  Bottom nav renders on /home
```

---

## Phase 3 — Events & RSVP

```
You are building Phase 3 — Events and RSVP for TMC.

MIGRATIONS:
  events: id, title, slug UNIQUE, description LONGTEXT,
    location_type ENUM('online','in_person','hybrid'),
    location_detail TEXT NULL, event_date DATETIME,
    end_date DATETIME NULL, cover_image_path NULL,
    external_link NULL,
    status ENUM('draft','published','cancelled','completed'),
    created_by FK, updated_by FK NULL, timestamps
    INDEX: (status, event_date)

  event_rsvps: id, event_id FK, user_id FK,
    rsvp_at TIMESTAMP, cancelled_at TIMESTAMP NULL
    UNIQUE(event_id, user_id)

RSVP SERVICE (App\Services\RsvpService):
  rsvp(User, Event): insert or restore (if cancelled_at not null, set to null)
  cancel(User, Event): set cancelled_at = now()
  isRsvpd(User, Event): bool
  On new RSVP: dispatch SendEventReminderNotification job
    Job fires 24h before event_date via Laravel Queue delay

LIVEWIRE COMPONENTS:
  App\Livewire\Events\EventsList (route: GET /events):
    Tabs: Upcoming | Past | My RSVPs
    Upcoming: published, event_date >= now(), ASC
    Past: published, event_date < now(), DESC
    My RSVPs: events the auth user has RSVPd to (cancelled_at null), future first
    Event card: cover image or teal placeholder, title, date, location badge,
      RSVP count, RSVP/Cancel button (Livewire wire:click action)

  App\Livewire\Events\EventDetail (route: GET /events/{slug}):
    Full event info, RSVP button
    On rsvp(): call RsvpService::rsvp(), flash confirmation
    On cancel(): call RsvpService::cancel()
    External link button if external_link set

FILAMENT EventResource:
  List with status badge, event_date, RSVP count
  Form: all fields, TipTap for description, image upload for cover
  Status actions: Publish, Cancel, Mark Completed
  Custom RelationManager or Action: RSVP list (name, email, rsvp_at)
  Export action: CSV of RSVPs
  Restrict to: admin, content_editor, super_admin

FEATURE TESTS:
  Member can RSVP and cancel RSVP
  Duplicate RSVP prevented (no duplicate rows)
  Cancelled event shows badge, RSVP preserved
  Only published events visible to members
  Admin can publish and cancel events
  RSVP reminder job dispatched with correct delay
```

---

## Phase 4 — Resources Library & Private Journal

```
You are building Phase 4 — Resources Library and Private Journal for TMC.

⚠️ CRITICAL: Journal privacy is non-negotiable. The body field must be
encrypted. No admin route can expose journal content. Test this explicitly.

MIGRATIONS:
  resources: id, title, slug UNIQUE, description TEXT,
    category ENUM('dua_book','dear_allah','pocket_guide','audio_halaqahs'),
    type ENUM('article','dua','pdf','audio','video_link','guide'),
    body LONGTEXT NULL, file_path NULL, external_url NULL,
    thumbnail_path NULL, status ENUM('draft','published','archived'),
    created_by FK, updated_by FK NULL, timestamps

  journal_entries: id, user_id FK,
    entry_date DATE,
    mood ENUM('happy','grateful','reflective','sad','anxious','neutral'),
    body LONGTEXT,  -- MUST have encrypted cast
    created_at, updated_at, deleted_at
    INDEX: (user_id, deleted_at)

  dua_list_items: id, user_id FK,
    resource_id FK NULL (saves from library),
    dua_text TEXT (for manually entered du'as),
    label VARCHAR(100) NULL,
    created_at, updated_at, deleted_at

JOURNAL ENTRY MODEL:
  protected $casts = ['body' => 'encrypted'];  // REQUIRED

JOURNAL POLICY (App\Policies\JournalEntryPolicy):
  All methods check: $user->id === $entry->user_id
  Admin roles are NOT exceptions — they are blocked
  Register in AuthServiceProvider

DUA LIST SERVICE (App\Services\DuaListService):
  save(User, Resource): insert with resource_id
  saveManual(User, string $text, ?string $label): insert with dua_text
  remove(User, DuaListItem): soft delete
  isSaved(User, Resource): bool

LIVEWIRE COMPONENTS:
  App\Livewire\Resources\ResourcesLibrary (route: GET /resources):
    Category tabs, search (wire:model.live)
    Resource cards with category badge + type icon
    Lazy loading / pagination (12 per page)

  App\Livewire\Resources\ResourceDetail (route: GET /resources/{slug}):
    Render by type (see PRD section 5D)
    Du'a type: Amiri font, rtl, "Save to My Du'a List" button
    wire:click saveToDuaList / removefromDuaList toggle
    Saved state: "Saved ✓" disabled state

  App\Livewire\Journal\JournalScreen (route: GET /journal):
    Two tabs: Entries | Du'a List

    ENTRIES TAB:
      List entries, ordered by entry_date DESC
      Row: mood emoji + date + 80-char preview (decrypt only for display)
      "+ New Entry" opens inline modal
      Modal: date (default today), 6 mood emoji buttons, textarea
      Edit: same form pre-populated
      Delete: confirm dialog, soft delete

    DUA LIST TAB:
      List dua_list_items for auth user
      Arabic text in Amiri font, rtl
      "From Library" badge for resource-saved items
      "+ Add Du'a" button: textarea + label field
      Delete: soft delete

FILAMENT ResourceResource:
  CRUD, TipTap body editor, file upload, thumbnail upload
  Restrict to: admin, content_editor, super_admin

FILAMENT — Journal privacy:
  In UserResource show page: show ONLY COUNT
    $user->journalEntries()->count()
  NEVER show body content. NEVER add a RelationManager for journal_entries.

FEATURE TESTS:
  Journal entry saves with encrypted body
  Encrypted value in database is not plaintext
  Member can read, edit, delete own entries
  HTTP request as admin to any journal route returns 403
  Du'a saves from resource detail page
  Du'a toggle removes item
  Resources filter by category correctly
```

---

## Phase 5 — Souq & Wallet

```
You are building Phase 5 — Souq Business Directory and Wallet for TMC.

MIGRATIONS:
  souq_listings: id, user_id FK, business_name, slug UNIQUE,
    category ENUM('fashion','food_catering','health_beauty',
      'education','services','creative','other'),
    description TEXT,  -- validate max 300 chars in Form Request
    contact_email, phone NULL, website NULL, instagram NULL,
    logo_path NULL,
    status ENUM('pending','approved','rejected','archived') DEFAULT 'pending',
    admin_note TEXT NULL,
    reviewed_by FK NULL, reviewed_at NULL,
    created_at, updated_at
    INDEX: (status)

  user_referrals: id, referrer_id FK(users), referred_id FK(users) UNIQUE,
    coins_awarded BOOLEAN DEFAULT false, created_at

COINS SERVICE (App\Services\CoinsService):
  getBalance(User): int — SUM(amount) from ledger for this user
  award(User, int $amount, string $reason, ?int $refId, ?string $note): void
  deduct(User, int $amount, string $reason, string $note): void
  getHistory(User): LengthAwarePaginator (10 per page, DESC)

LIVEWIRE COMPONENTS:
  App\Livewire\Souq\SouqDirectory (route: GET /souq):
    Grid 2-col mobile, 3-col desktop
    Search wire:model.live, category filter tabs
    Show approved listings only
    Listing card: logo (or initials circle fallback), name, category badge, desc
    "List My Business" button → /souq/apply (auth only)

  App\Livewire\Souq\ListingDetail (route: GET /souq/{slug}):
    Full listing: logo, name, category, description
    Contact row: email (mailto) + phone + website + Instagram (icon + link)

  App\Livewire\Souq\ApplyForm (route: GET /souq/apply):
    If user has approved listing: show it, no form
    If user has pending application: show status message, no form
    Otherwise: show form
    Form: business_name, category SELECT, description (textarea, 300 char counter),
      contact_email, phone (optional), website (optional), instagram (optional),
      logo file upload (image only, max 2MB)
    On submit: create souq_listing (status=pending)
    Flash: "Application submitted — we'll review within 48 hours, insha'Allah"

  App\Livewire\Wallet\WalletScreen (route: GET /wallet):
    Section A: balance (Dancing Script, large, gold)
    Section B: referral link + Alpine.js copy button + referral count
    Section C: placeholder catalog (3 greyed cards, lock icons, "Coming soon")
    Section D: collapsible history (CoinsService::getHistory)
      Date | reason label | +/- amount (green/red)

FILAMENT SouqListingResource:
  List with status filter (pending first)
  Approve action: set approved, reviewed_by, reviewed_at
    → dispatch SouqApprovedNotification job (in-app + push notification)
    → log to audit_logs via AuditLogService
  Reject action: modal for admin_note, set rejected, log
  Edit and archive actions
  Restrict to: admin, super_admin

FILAMENT UserResource — add Coins section to show page:
  Display current balance (CoinsService::getBalance)
  Last 5 transactions table
  Award Coins action: modal (amount INT, reason TEXT)
    → CoinsService::award(), log to audit_logs
  Deduct Coins action: same pattern

FEATURE TESTS:
  Only approved listings visible in /souq
  Member cannot see pending/rejected listings
  Apply form prevents duplicate submissions
  Admin approves listing → status changes → notification dispatched
  Coins balance = sum of ledger
  Referral link uses correct referral_code
  Admin award increases balance and logs
```

---

## Phase 6 — Community, Profile, Legacy Card & Announcements

```
You are building Phase 6 — Community Spaces, Profile, Legacy Card,
and Announcements for TMC.

MIGRATIONS:
  community_spaces: id, name, slug UNIQUE, short_description,
    description LONGTEXT, guidelines LONGTEXT NULL,
    cover_image_path NULL, external_link NULL,
    is_youth_space BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    sort_order INT DEFAULT 0, created_at, updated_at

  support_applications: id, user_id FK NULL,
    type ENUM('volunteer','mentorship'), name, email,
    motivation TEXT, skills_or_focus TEXT, availability NULL,
    status ENUM('pending','reviewed','accepted','declined') DEFAULT 'pending',
    admin_notes NULL, reviewed_by FK NULL, reviewed_at NULL, created_at

  badges: id, name, description, icon_path, criteria TEXT, is_active BOOLEAN
  user_badges: id, user_id FK, badge_id FK, awarded_at, awarded_by FK NULL

SEEDERS:
  CommunitySpaceSeeder: 3 spaces
    TMC Monthly Discussions, SISTEEN Space (is_youth_space=true), General Reflections

LIVEWIRE COMPONENTS:
  App\Livewire\Community\CommunityHome (route: GET /community):
    Section A: spaces grid (active only), name + short_description + cover
    Section B: Support TMC cards (Volunteer, Mentorship, Donate)

  App\Livewire\Community\SpaceDetail (route: GET /community/spaces/{slug}):
    Description, guidelines card (border-left 3px gold if exists)
    Related events (upcoming, max 3)
    Related resources (published, max 3)
    External link button if set

  App\Livewire\Community\SupportForm (route: GET /community/support/{type}):
    If pending application exists: show status, no form
    Fields: name (pre-filled), email (pre-filled), skills_or_focus,
      motivation, availability
    On submit: create support_application
    Flash: "JazakAllahu Khairan — we'll be in touch insha'Allah"

  Blade view /community/donate:
    Bank details from settings (key: 'bank_details')
    Donation message from settings (key: 'donate_message')
    Arabic بارك الله فيكم — Amiri centred, gold 50% opacity

  App\Livewire\Profile\ProfileScreen (route: GET /profile):
    Avatar (80px, img or initials)
    Name Dancing Script 2rem, role badge pill teal
    Stats row: Member since | Coins | Badges
    Interests chips (teal-lt, Dancing Script 0.9rem)
    Badges horizontal scroll
    Legacy Card preview → full card on tap
    Settings links: Notifications | Change password

  App\Livewire\Profile\EditProfile (route: GET /profile/edit):
    display_name, avatar upload (image, max 2MB), interests chips, goals chips
    Email and role: display only

  App\Livewire\Profile\LegacyCard (route: GET /profile/legacy-card):
    Full-screen digital card — see DESIGN_GUIDE.md for exact spec
    Web Share API for share button, fallback to screenshot instructions

  App\Livewire\Profile\NotificationPreferences (route: GET /profile/notifications):
    Toggle switches, saves to user_profiles.notification_preferences JSON

ANNOUNCEMENTS SCHEDULER:
  In routes/console.php or Console\Kernel:
    Schedule: every minute
    Set status='published', published_at=now()
    Where status='scheduled' AND publish_at <= now()

FILAMENT RESOURCES:
  CommunitySpaceResource: CRUD, sort_order, is_active toggle, guidelines editor
  SupportApplicationResource: list by type/status, review action, accept/decline
    All actions logged
  AnnouncementResource: title, body (TipTap), status, schedule datetime
  BadgeResource: CRUD
    On UserResource show page: "Award Badge" action → user_badges row + audit log
  Restrict to: admin, content_editor, super_admin
```

---

## Phase 7 — Admin Dashboard

```
You are building Phase 7 — Complete Admin Dashboard for TMC.
All Filament resources were partially built in earlier phases.
This phase adds the overview dashboard, user governance, and broadcast.

MIGRATION:
  user_role_history: id, user_id FK, changed_by FK(users),
    old_role VARCHAR(50), new_role VARCHAR(50),
    reason TEXT NULL, created_at

DASHBOARD PAGE (Filament default /admin page):
  Replace default with a custom Dashboard using these Filament Widgets:

  StatsOverviewWidget:
    Total members (users count, non-deleted)
    Active last 30 days (users with session in last 30 days)
    Pending Souq applications (status=pending count)
    Upcoming events (published, future, count)
    Total coins awarded (SUM of positive ledger entries)

  LatestApplicationsWidget (Table widget):
    Last 5 support_applications: name, type, created_at, status

  RecentActivityWidget (Table widget):
    Last 10 audit_logs: actor name, action, created_at

FULL USER MANAGEMENT (UserResource):
  List: name, email, role badge, status badge, created_at, coins balance
  Filter: by role (select), by status (active/suspended)
  Search: name or email
  Show page tabs:
    Overview: profile, interests, goals
    Coins: balance + last 5 transactions + Award/Deduct actions
    Activity: RSVP count, Souq listings count, journal entry COUNT ONLY
    Badges: list + Award Badge action
  Actions:
    Suspend: modal for reason, set status+suspended_at+suspended_reason
      → log to audit_logs
    Reactivate: clear suspension fields → log
    Soft Delete (super_admin only) → log

ROLE MANAGEMENT (super_admin only):
  On UserResource show page — gate with canAccess() for super_admin only:
  "Change Role" action:
    Modal: role select (all 6 roles), optional reason textarea
    On save:
      $user->syncRoles([$newRole])
      Insert user_role_history: user_id, changed_by=auth()->id(),
        old_role, new_role, reason
      AuditLogService::log('role_changed', $user, ['role'=>$oldRole], ['role'=>$newRole])
    Show warning if demoting an admin

AUDIT LOG VIEWER (AuditLogResource):
  List: actor, action, target, IP, created_at
  Filter: by action (text), by actor (select), by date range
  Read-only — NO create, edit, or delete actions
  Restrict to: super_admin ONLY

BROADCAST NOTIFICATIONS (Custom Filament Page):
  Path: /admin/broadcast
  Form fields:
    Title (text), Body (textarea)
    Audience: All members | By interest (multi-select) | By goal (multi-select)
  Preview count: live query "This will reach X members"
  On send:
    Create notification_logs row
    Dispatch BroadcastPushNotification job to queue
    AuditLogService::log('broadcast_sent', null, [], ['title'=>$title, 'audience'=>$audience])
  Below form: history table of past broadcasts

SETTINGS PAGE (Custom Filament Page):
  Path: /admin/settings
  Key-value form managing settings table:
    bank_details (textarea)
    donate_message (textarea)
    starter_coins_amount (number input, default 50)
    referral_coins_amount (number input, default 25)
  Restrict to: super_admin ONLY

FEATURE TESTS:
  Stats widget counts match database
  Super Admin can change a user's role
  Role change creates user_role_history row
  Role change creates audit_log row
  Non-super-admin cannot access role change action
  Suspend blocks user login
  Reactivate restores login
  Audit log is read-only (no create/edit routes exist)
```

---

## Phase 8 — Push Notifications & PWA

```
You are building Phase 8 — Push Notifications and PWA for TMC.

INSTALL:
  composer require minishlink/web-push
  php artisan webpush:vapid
  (stores VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT in .env)

MIGRATION:
  push_subscriptions: id, user_id FK,
    endpoint TEXT UNIQUE, public_key TEXT, auth_token TEXT,
    created_at, updated_at

PWA MANIFEST (public/manifest.json):
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
  Link in layouts/app.blade.php <head>:
  <link rel="manifest" href="/manifest.json">

SERVICE WORKER (public/sw.js):
  Cache name: tmc-v1
  Install event: cache /, /home, /offline, /css/style.css,
    /js/app.js, /manifest.json, /images/img1.png
  Fetch event:
    Static assets (/css/, /js/, /images/): cache-first
    Navigation requests: network-first, fallback to /offline
    API/dynamic: network-first, no fallback
  Push event: show notification with title, body, icon, url

OFFLINE FALLBACK (resources/views/offline.blade.php):
  Standalone Blade file — no @extends, no asset loading from network
  Inline CSS only. img1 logo inline SVG or base64.
  Dancing Script via data URI or system fallback.
  Message: "You're offline" + "Check your connection and try again"

SERVICE WORKER REGISTRATION:
  In layouts/app.blade.php (before </body>):
  <script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => {
        // Track visit count for push prompt
        let visits = parseInt(localStorage.getItem('tmc_visits') || '0') + 1;
        localStorage.setItem('tmc_visits', visits);
        if (visits >= 2) { requestPushPermission(reg); }
      });
  }
  async function requestPushPermission(reg) {
    if (Notification.permission === 'granted') { subscribeToPush(reg); return; }
    if (Notification.permission === 'denied') { return; }
    const perm = await Notification.requestPermission();
    if (perm === 'granted') { subscribeToPush(reg); }
  }
  async function subscribeToPush(reg) {
    const vapidKey = '{{ config("webpush.vapid.public_key") }}';
    const sub = await reg.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: vapidKey
    });
    await fetch('/push/subscribe', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
      body: JSON.stringify(sub)
    });
  }
  </script>

PUSH ROUTES:
  POST /push/subscribe → PushSubscriptionController@store
    Create or update push_subscriptions for auth user
  DELETE /push/subscribe → @destroy
    Remove subscription for auth user

PUSH NOTIFICATION SERVICE (App\Services\PushNotificationService):
  send(User $user, string $title, string $body, ?string $url = null): void
    Get user's push_subscription
    Send via WebPush library
    On expired/invalid: delete subscription, continue (no exception)
  sendToMany(Collection $users, ...): void
    Batch send using WebPush::sendNotifications()

QUEUED JOBS — wire up all from earlier phases:
  SendEventReminderNotification:
    PushNotificationService::send() + create database notification
    Delay: calculated from event_date - 24 hours

  SouqApprovedNotification:
    PushNotificationService::send() + database notification

  BroadcastPushNotification (created in Phase 7):
    Query users by audience filter
    PushNotificationService::sendToMany()
    Update notification_logs.delivery_count

IN-APP NOTIFICATION FEED (Livewire: App\Livewire\Notifications\Bell):
  Uses Laravel database notification channel
  Bell icon with unread count badge (red circle)
  Dropdown: last 10 notifications, mark-as-read on click
  "View all" → GET /notifications (full Livewire page)

PWA INSTALL PROMPT:
  Capture beforeinstallprompt in layouts/app.blade.php
  After 3rd visit (localStorage counter), show a branded bottom banner:
    "Add TMC to your home screen"
    Install button (calls prompt.prompt())
    Dismiss button (stores dismissed in localStorage)
  iOS: detect iOS and show manual instructions instead
    "Tap Share → Add to Home Screen"

FEATURE TESTS:
  GET /manifest.json returns valid manifest with correct content-type
  POST /push/subscribe saves subscription to database
  DELETE /push/subscribe removes it
  PushNotificationService::send() handles missing subscription without throwing
  Database notification created alongside push
  GET /offline returns 200
```

---

## Phase 9 — QA & Deployment

```
You are running final QA and deploying TMC to production.
No new features in this phase.

STEP 1 — Full test suite
  php artisan test --coverage
  Must pass all tests from phases 1–8.
  Fix any failures before proceeding.

STEP 2 — Security checklist
  [ ] Every form has @csrf
  [ ] All user content rendered with {{ }} not {!! !!}
  [ ] File uploads validated by MIME type (not just extension)
      Use: $request->file('logo')->getMimeType()
  [ ] All Livewire components call $this->authorize() or Gate::authorize()
      before any data mutation
  [ ] journal_entries.body confirmed encrypted in database
      (SELECT body FROM journal_entries LIMIT 1 — should be base64-like string)
  [ ] No route exists that exposes journal content to admin
  [ ] Rate limiting active on /login and /register

STEP 3 — Performance
  php artisan optimize
  Add all database indexes from TRD.md section 12
  Review for N+1 queries (use Laravel Debugbar in staging)
  Add eager loading where needed
  Install intervention/image, resize uploads to max 800px on save

STEP 4 — Production server
  Ubuntu 22.04, PHP 8.2, MySQL 8.0, Nginx
  Install Let's Encrypt SSL (certbot):
    sudo certbot --nginx -d yourdomain.com
  HTTPS is required for service worker and push notifications.

  .env production values:
    APP_ENV=production
    APP_DEBUG=false
    APP_URL=https://yourdomain.com
    QUEUE_CONNECTION=redis
    SESSION_DRIVER=redis
    MAIL_MAILER=smtp (configure with real SMTP)

  Deploy commands:
    composer install --no-dev --optimize-autoloader
    php artisan migrate --force
    php artisan db:seed --class=RoleSeeder
    php artisan db:seed --class=AdminUserSeeder
    php artisan optimize
    npm run build

  Supervisor config (/etc/supervisor/conf.d/tmc-worker.conf):
    [program:tmc-worker]
    command=php /var/www/tmc/artisan queue:work --queue=notifications,broadcasts,emails,default --tries=3
    autostart=true
    autorestart=true

  Cron:
    * * * * * cd /var/www/tmc && php artisan schedule:run >> /dev/null 2>&1

  Backups (Spatie Laravel Backup):
    composer require spatie/laravel-backup
    Configure: daily at 2am, S3 or Backblaze B2, keep 7 days

STEP 5 — Post-deploy smoke test
  Perform these manually on the live URL:
  [ ] GET / — landing page loads, looks identical to GitHub Pages version
  [ ] GET /register → complete registration
  [ ] Verify email
  [ ] Complete all 4 onboarding steps
  [ ] 50 coins visible in /wallet
  [ ] RSVP to a test event
  [ ] Write a journal entry, confirm it saves
  [ ] Submit a Souq application
  [ ] GET /admin — login works
  [ ] Stats on admin dashboard show correct numbers
  [ ] Change a user's role as super_admin
  [ ] Confirm role change in audit log
  [ ] Send a test broadcast notification
  [ ] Install PWA on Android Chrome
  [ ] Install PWA on iPhone Safari (manual Add to Home Screen)
  [ ] Disconnect from WiFi, visit a cached page → loads
  [ ] Visit an uncached page offline → /offline fallback shows
  [ ] Run Lighthouse on /home → PWA score 80+
```

---

## Quick Reference

| Phase | Name | Days |
|-------|------|------|
| 0 | Foundation & Setup | 3–5 |
| 1 | Auth & Onboarding | 4–5 |
| 2 | Home Dashboard | 2–3 |
| 3 | Events & RSVP | 3–4 |
| 4 | Resources & Journal | 5–6 |
| 5 | Souq & Wallet | 4–5 |
| 6 | Community, Profile, Announcements | 5–6 |
| 7 | Admin Dashboard | 7–10 |
| 8 | Push Notifications & PWA | 3–4 |
| 9 | QA & Deployment | 5–7 |
| **Total** | | **41–55 days** |
