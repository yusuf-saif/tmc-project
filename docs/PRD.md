# The Muhsinat Club (TMC) — Product Requirements Document
**Version:** 2.0  
**Status:** Approved for development  
**Tagline:** Hunting Ajr for the Home in Jannah!

---

## 1. Product Overview

The Muhsinat Club is a mobile-first Progressive Web App (PWA) — a curated digital home for Muslim women where faith, community, growth, and rewards come together in one elegant experience.

**Two core experiences:**
- **Member PWA** — installable on any smartphone, no app store required
- **Admin Dashboard** — Filament-powered panel for the TMC team

The platform is intentionally curated. It is not an open social network. Content is moderated. The tone is warm, feminine, premium, and faith-driven.

---

## 2. Problem Statement

**For members:** Muslim women in the TMC community have no dedicated digital home that brings together spiritual content, community events, a private journal, a rewards system, and a curated Muslim business directory — all in a safe, moderated, faith-aligned space.

**For the TMC team:** No central tool exists to publish content, manage members, track engagement, award coins, approve business listings, or send community-wide communications.

---

## 3. User Roles

| Role | Description |
|------|-------------|
| `member` | Default role. Full access to member features. |
| `volunteer` | Member who applied and was approved. Same access + visible volunteer badge. |
| `moderator` | Reviews and actions member-submitted content. |
| `content_editor` | Creates and publishes official content, events, resources. |
| `admin` | Manages users, content, Souq, coins, and community day-to-day. |
| `super_admin` | Full platform control — roles, settings, audit log. |

---

## 4. User Personas

### Fatimah (Member)
Muslim woman, 25–40, smartphone-first. Wants a calm daily spiritual touchpoint, a private journal, halaqah updates, and a place to discover sisters' businesses.

### Umm Khalid (Content Editor)
TMC team member. Needs to draft, schedule, and publish daily content without developer help.

### Sister Admin (Admin)
Senior TMC staff. Manages members, approves Souq listings, sends broadcast notifications, reviews support applications.

### Founding Director (Super Admin)
Platform owner. Manages roles, reviews audit logs, controls global settings.

---

## 5. MVP Feature Modules

### 5A. Authentication & Onboarding

**Members can:**
- Register with name, email, password
- Verify email before accessing the app
- Reset password via email link
- Complete a 4-step onboarding wizard
- Use a referral link to invite others (earns coins)

**Onboarding steps:**
1. Select interests (min 1, max 5 from preset list)
2. Select goals (Community / Learning / Business / Volunteering)
3. Set notification preferences (toggles per category)
4. Welcome screen — 50 Jannah Coins awarded, redirect to `/home`

**Rules:**
- Members who have not completed onboarding are blocked from all app routes
- 50 coins awarded exactly once per member on onboarding completion
- Referrer earns 25 coins when referred member verifies email

---

### 5B. Home Dashboard

**Members see:**
- Personalised greeting: "Assalamu Alaykum, [first name]"
- Rotating daily Islamic phrase
- Announcement banner (gold, shows latest published announcement)
- Jannah Coins balance card
- Upcoming events preview (next 3)
- Quick actions: Journal · Du'a Book · Events · Souq
- Support TMC soft banner

**Rules:**
- Announcement hidden if none published
- Events section shows empty state if no published events
- Bottom navigation (7 tabs) present on all member screens

---

### 5C. Events & RSVP

**Members can:**
- Browse events (Upcoming / Past / My RSVPs tabs)
- View full event detail page
- RSVP with one tap (button changes to "You're going ✓")
- Cancel RSVP
- Receive a push reminder 24h before RSVPd event

**Event statuses:** Draft → Published → Cancelled / Completed

**Admins can:**
- Create, edit, publish, cancel, and complete events
- Upload cover image
- View RSVP list per event
- Export RSVP list as CSV

**Rules:**
- One RSVP per member per event
- Cancelled events remain visible with a "Cancelled" badge

---

### 5D. Resources Library

**Members can:**
- Browse resources by category (Du'a Book / Dear Allah / Pocket Guides / Audio & Halaqahs)
- Search by title
- View resource detail (rendered by type)
- Save du'a-type resources to personal Du'a List

**Resource types and rendering:**
| Type | Renders as |
|------|-----------|
| `article` / `guide` | Formatted prose body |
| `dua` | Amiri font, RTL, "Save to Du'a List" button |
| `pdf` | Download link + in-page iframe |
| `audio` | HTML5 audio player |
| `video_link` | Link opens in new tab |

**Admins can:**
- Create, edit, publish, and archive resources
- Upload PDFs and audio files
- Write rich-text body (TipTap editor)

---

### 5E. Private Journal

**Members can:**
- Create journal entries (date + mood + body text)
- View, edit, and delete own entries
- Save du'as to a personal Du'a List (manually or from Resources)

**Mood options:** Happy 😊 · Grateful 🤲 · Reflective 🌙 · Sad 😔 · Anxious 😟 · Neutral 😐

**Privacy rules (non-negotiable):**
- Journal body text is encrypted at rest (`encrypted` cast on Eloquent model)
- No admin, moderator, or any other user can read journal content
- Admin panel shows only entry count per user — never content
- A Laravel Policy enforces this at the code level
- This must be tested and confirmed before any deployment

---

### 5F. Souq — Business Directory

**Members can:**
- Browse approved business listings (grid + search + category filter)
- View full listing detail page
- Apply to list their own business
- See application status if already submitted

**Business categories:** Fashion · Food & Catering · Health & Beauty · Education · Services · Creative · Other

**Admins can:**
- Review pending Souq applications
- Approve (listing goes live) or reject (with admin note)
- Edit and archive live listings

**Rules:**
- Only approved listings visible to members
- One active application per member at a time
- Approval triggers an in-app + push notification to applicant
- All admin actions logged to audit_logs

---

### 5G. Wallet — Jannah Coins

**Members can:**
- View current Jannah Coins balance
- View their unique referral link (copy button)
- See referral count
- View transaction history (10 per page, collapsible)
- See a placeholder rewards catalog ("Coming soon")

**Coins are earned by:**
| Trigger | Amount |
|---------|--------|
| Complete onboarding | 50 coins |
| Successful referral | 25 coins per referral |

**Rules:**
- Balance is always computed from the ledger (no single balance column)
- Coins cannot be spent in MVP (redemption catalog is Phase 2)
- Admins can manually award or deduct coins with a required reason note

---

### 5H. Community Spaces

**Members can:**
- Browse active community spaces (grid)
- View space detail: description, guidelines, related events, related resources, optional external link

**Admins can:**
- Create, edit, activate, and deactivate spaces
- Set guidelines, cover image, and external link per space

**Rules:**
- No live chat inside spaces in MVP (Phase 2)
- SISTEEN Space flagged as youth space — adult content must not be placed there

---

### 5I. Support TMC

**Members can:**
- Submit a volunteer application
- Submit a mentorship application
- View bank transfer details for donation (static page)

**Rules:**
- Prevent duplicate pending applications (show status if already submitted)
- Bank details and donation message stored in settings table (editable by Super Admin)

**Admins can:**
- View all support applications
- Mark as reviewed, accepted, or declined
- Add admin notes

---

### 5J. Profile & Legacy Card

**Members can:**
- View profile: avatar, name, role badge, member since, coins, interests, goals, badges
- Edit: display name, avatar, interests, goals
- View and screenshot Legacy Card
- Update notification preferences
- Change password

**Legacy Card displays:**
- TMC logo (img1.png)
- Arabic: المحسنات (Amiri font)
- Member display name (Dancing Script)
- Membership tier label
- Member since date
- Jannah Coins balance
- Teal background + botanical pattern (img4.png)

**Rules:**
- Members cannot change their own email or role
- Avatar: images only, max 2MB

---

### 5K. Announcements

**Admins can:**
- Create announcements with title and rich-text body
- Schedule for a future publish date/time
- Archive announcements

**Members see:**
- Latest published announcement as a gold banner on the home screen
- Full announcement on tap
- Banner hidden if no announcements exist

**Rules:**
- Laravel Scheduler checks every minute and auto-publishes scheduled announcements

---

### 5L. Push Notifications & PWA

**PWA requirements:**
- Web app manifest (name, icons, theme_color #1A6B72, display: standalone)
- Service worker with static asset caching
- Offline fallback page (branded, no network required)
- "Add to Home Screen" prompt after 2nd qualifying visit
- iOS Safari manual instructions shown

**Push notification types:**
| Type | Trigger |
|------|---------|
| Welcome coins | Onboarding completion |
| Event reminder | 24h before RSVPd event |
| RSVP confirmation | Immediately on RSVP |
| Souq listing approved | Admin approves listing |
| Broadcast | Admin sends manually |
| Referral coins earned | Referred member registers |

**Rules:**
- Permission requested after 2nd visit (not on first load)
- All push notifications also create an in-app database notification
- In-app notification feed: bell icon, unread count, last 10, mark-as-read

---

### 5M. Admin Dashboard

**Super Admin and Admin can:**
- View live stats: total members, active last 30 days, pending Souq applications, upcoming events, total coins awarded
- Manage all users (search, filter, view, suspend, reactivate)
- View member profile detail (never journal content)
- Change user roles (Super Admin only)
- View audit log (Super Admin only, read-only)
- Send broadcast notifications
- Manage settings (bank details, coins amounts)
- Manage interests, goals, badges, community spaces

**Audit log records:**
- Every role change (with old role, new role, who changed it, why)
- Every suspension and reactivation
- Every Souq approval/rejection
- Every manual coins adjustment
- Every admin content action

---

## 6. Non-Goals (MVP)

These are explicitly out of scope. Do not build them.

| Feature | Phase |
|---------|-------|
| Live chat rooms | Phase 2 |
| Direct messages | Phase 2 |
| Jannah Coins redemption catalog | Phase 2 |
| QR check-in for events | Phase 2 |
| Payment gateway / paid events | Phase 2 |
| Event replay library | Phase 2 |
| Full vendor self-service dashboard | Phase 2 |
| Social follow / member discovery | Phase 2 |
| Native iOS / Android apps | Scale |
| AI concierge | Scale |

---

## 7. Acceptance Criteria Summary

| Module | Must pass |
|--------|-----------|
| Auth | Register → verify → onboard → home works end to end |
| Onboarding | 50 coins awarded exactly once |
| Referral | 25 coins awarded to referrer after referred member verifies |
| RSVP | Create, cancel, duplicate prevention all work |
| Journal | Admin cannot access body content via any route |
| Journal | body field encrypted at rest in database |
| Souq | Listing only visible after admin approval |
| Coins | Balance always matches ledger sum |
| Role change | Logged in user_role_history and audit_logs |
| PWA | Lighthouse PWA score 80+ on mobile |
| Landing page | Unchanged at / throughout every phase |
