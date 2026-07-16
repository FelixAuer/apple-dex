# Apple-Dex — Specification & Implementation Plan

A personal "Pokedex for apples": a small, self-hosted web app for logging apple varieties the user has eaten. Each variety can be caught exactly once per user. No social features, no AI, no external paid services.

**Target audience of this document:** an AI coding agent implementing the app from scratch. Follow this spec closely; where details are unspecified, prefer the simplest solution consistent with the stated design principles.

---

## 1. Product overview

### 1.1 Concept

The user maintains a personal collection ("dex") of apple varieties. The app ships with a seeded catalog of common and regional varieties. Varieties the user has logged ("caught") appear in full color with the user's own photo; varieties not yet tried appear greyed out. Users can add custom varieties for heirloom/regional apples not in the catalog.

### 1.2 Design principles

1. **Mobile-first.** Primary usage is one-handed on a phone, often in a store or kitchen. Desktop must work but is secondary.
2. **Speed over features.** The two core interactions — "have I had this variety?" and "log this apple" — must each complete in under 10 seconds / 3–4 taps.
3. **Nothing is required except the variety name.** Photo, location, and notes are always optional. Never block a save on optional data.
4. **The user's photo is the trophy.** Caught varieties display the user's own catch photo, not stock imagery.
5. **Zero running costs** beyond the existing server: no AI APIs, no paid geocoding, no third-party SaaS.

### 1.3 Explicit non-goals

- No social or interactive features of any kind. Users never see other users' data.
- No AI-based recognition.
- No multiple catches per variety (one catch per user per variety, enforced at DB level).
- No ratings, flavor profiles, or structured tasting data. Free-text notes only.
- No native mobile apps. PWA only.

---

## 2. Tech stack

| Concern | Choice |
|---|---|
| Framework | Laravel 12 (latest stable), PHP 8.3+ |
| Frontend | Livewire 3 + Volt (class-based or functional, agent's choice, but be consistent), Tailwind CSS 4, Alpine.js (ships with Livewire) |
| Database | MySQL 8 (use standard Laravel migrations; nothing MySQL-specific so Postgres would also work) |
| Auth | Laravel Breeze (Livewire stack), standard session auth |
| Media | `spatie/laravel-medialibrary` for catch photos and variety reference photos, with generated thumbnail conversions |
| Maps/Geo | Browser Geolocation API (client-side only). No server-side geocoding service. Location label is a free-text field the user types. |
| PWA | Static `manifest.json` + minimal hand-rolled service worker (app-shell caching only; the app is online-only — offline support is out of scope) |
| Testing | Pest, feature tests for all core flows |
| Deployment | Laravel Forge (standard zero-downtime deploy script). Provide `.env.example` with all required keys. |

Do not add packages beyond these plus their dependencies without clear necessity.

---

## 3. Data model

### 3.1 `users`

Standard Laravel/Breeze users table. No modifications needed.

### 3.2 `varieties`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(100) | Display name, e.g. "Granny Smith" |
| `origin` | string(150), nullable | Free text, e.g. "Australia, 1868" or "Steiermark, Österreich" |
| `user_id` | FK → users, nullable, on delete cascade | `NULL` = global seeded variety visible to all users. Set = custom variety, visible only to that user. |
| `created_at` / `updated_at` | timestamps | |

Constraints and indexes:
- Unique composite index on (`name`, `user_id`). Note: MySQL treats NULLs as distinct in unique indexes, so global uniqueness of seeded names must additionally be guaranteed by the seeder itself (seed with `updateOrCreate` on name where `user_id IS NULL`).
- When a user creates a custom variety, validate case-insensitively that the name does not collide with an existing global variety or one of their own customs.

Reference photo: attached via medialibrary collection `reference_photo` (single file), nullable. Seeded varieties may ship without reference photos; the UI must handle absence gracefully (generic apple silhouette placeholder).

### 3.3 `catches`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → users, on delete cascade | |
| `variety_id` | FK → varieties, on delete cascade | |
| `caught_at` | date | Defaults to today in the form; user-editable; no future dates allowed |
| `lat` | decimal(10,7), nullable | |
| `lng` | decimal(10,7), nullable | |
| `location_label` | string(150), nullable | Free text, e.g. "Naschmarkt, Wien" |
| `notes` | text, nullable | |
| `created_at` / `updated_at` | timestamps | |

Constraints:
- Unique composite index on (`user_id`, `variety_id`) — this enforces the one-catch-per-variety rule.
- Catch photo: medialibrary collection `photo` (single file), nullable.

### 3.4 Authorization rules

- A user can see: all global varieties (`user_id IS NULL`) + their own custom varieties. Never another user's customs.
- A user can see/edit/delete only their own catches.
- A user can edit/delete only their own custom varieties. Global varieties are read-only for users.
- Deleting a custom variety also deletes the user's catch of it (cascade) — confirm with the user in the UI before deleting.
- Implement via Laravel policies (`VarietyPolicy`, `CatchPolicy`) and scoped queries (e.g. a `Variety::visibleTo($user)` local scope). Every Livewire component must apply these scopes; never trust IDs from the client.

### 3.5 Model naming note

`Catch` is a reserved-adjacent word in PHP contexts but is a valid class name in PHP 8; however, to avoid confusion, name the Eloquent model `AppleCatch` with `protected $table = 'catches'`, or alternatively name the model `Catch` if it causes no tooling issues. Agent's choice; prioritize working code.

---

## 4. Seed data

Provide a `VarietySeeder` that seeds **global varieties** (`user_id = NULL`) using `updateOrCreate` keyed on name, so re-running is idempotent and safe in production.

Curate roughly **70–90 varieties** targeted at availability in **Austria / Central Europe**. Include:

1. **International commercial standards:** Granny Smith, Golden Delicious, Red Delicious, Gala, Fuji, Braeburn, Jonagold, Elstar, Idared, Pink Lady (Cripps Pink), Jazz (Scifresh), Envy, Kanzi, Opal, Cosmic Crisp, Honeycrisp, Ambrosia, Cameo, Rubens, Wellant, etc.
2. **German/Austrian classics and heirlooms:** Boskoop (Schöner aus Boskoop), Kronprinz Rudolf, Ilzer Rosenapfel, Steirischer Maschanzker, Gravensteiner, Berlepsch, Cox Orange, Topaz, Rubinette, Berner Rosenapfel, Alkmene, Holsteiner Cox, Ontarioapfel, Roter Eiserapfel, Lavanttaler Bananenapfel, Weißer Klarapfel, James Grieve, Goldparmäne, Landsberger Renette, Champagner Renette, etc.
3. Fill the remainder with other varieties plausibly found in Austrian supermarkets, farmers' markets, and orchards.

Each seed entry: `name` plus a short `origin` string (country/region and, where known, approximate date of origin). Reference photos are **not** required for seeding; leave them empty.

Also provide a `DatabaseSeeder` that runs `VarietySeeder` and, in local environment only, creates a test user (`test@example.com` / password `password`).

---

## 5. Screens & UX specification

There are four screens. All screens are mobile-first (design for ~390px width, scale up gracefully).

### 5.1 Dex (home, route `/`, auth required)

The main screen. Contents top to bottom:

1. **Header:** app name, small link to settings/logout.
2. **Completion counter:** "Gefangen: 34 / 87" style counter (caught / total visible varieties for this user). Language: English is fine throughout the app; the counter format is what matters.
3. **Search bar:** filters the grid live (Livewire, debounced ~250ms). Matching is case-insensitive substring; if easy, add basic fuzziness (e.g. also match with umlauts normalized: "gravensteiner" matches "Gravensteiner"). Searching is the "have I had this?" interaction — it must feel instant.
4. **Sort toggle:** three options — "Recently caught" (default), "A–Z", "Uncaught first". Persist the choice in the session.
5. **Grid:** 3 columns on mobile, more on wider screens. Each tile shows:
   - **Caught variety:** the user's catch photo as tile background (thumbnail conversion), variety name, caught date. Full color.
   - **Uncaught variety:** reference photo if present, else a generic apple silhouette (a single bundled SVG asset); rendered greyed out / desaturated / reduced opacity. Name visible.
   - Tap → Variety card.
6. **Floating action button ("+"):** bottom-right, always visible, opens the New Catch screen.

Empty states: if search matches nothing, show a friendly empty state with a button "Create variety '\<query\>'" that jumps into the New Catch flow with that name prefilled as a new custom variety.

### 5.2 Variety card (route `/varieties/{id}`, auth + visibility check)

Two states:

**Caught:**
- Large catch photo (or placeholder if none), variety name, origin.
- Caught date, location label (and a small static map link — a plain link to OpenStreetMap at lat/lng, no embedded map — only if coordinates exist).
- Notes.
- Actions: "Edit catch" (opens the same form as New Catch, prefilled), "Delete catch" (confirmation dialog; deleting a catch returns the variety to uncaught state).
- If the variety is the user's own custom variety: also "Edit variety" (name/origin/reference photo) and "Delete variety" (confirmation; warns that the catch will be deleted too).

**Uncaught:**
- Reference photo or silhouette, name, origin, and a prominent "Catch it!" button that opens New Catch with this variety preselected.
- Custom-variety edit/delete actions as above if applicable.

### 5.3 New Catch (route `/catch/new`, auth required)

A single Livewire form, structured as one screen (no multi-step wizard):

1. **Variety field (the star of the form):** autocomplete search over visible varieties. As the user types, show up to ~8 matches. Behavior details:
   - If the typed text matches no variety, the last suggestion is always: **"➕ Create '\<typed text\>' as new variety"**. Selecting it inlines two extra optional fields (origin, reference photo) but does not navigate away; the custom variety is created in the same transaction as the catch on save.
   - If the user selects a variety they have **already caught**, do not show the form — instead show an inline notice "Already caught on \<date\>" with a link to that variety card. This makes the check and log flows converge.
   - Support preselection via query param (`?variety=ID`) for the "Catch it!" button, and name prefill via `?name=...` for the dex empty state.
2. **Photo:** native file input with `capture="environment"` so mobile browsers open the camera directly. Optional. Show a preview after selection. Client-side hint to keep uploads reasonable; server-side validate (image, max ~10 MB) and let medialibrary generate a web-friendly conversion (max ~1600px long edge) plus a thumbnail (~400px square crop) for grid tiles.
3. **Date:** date input, default today, max today.
4. **Location:** a "Use my location" button that requests browser geolocation and fills hidden lat/lng fields plus shows the coordinates were captured; a free-text `location_label` input the user can type ("Billa Mariahilferstraße", "Omas Garten"). Geolocation failure or denial must fail silently into manual-only mode — never block or nag.
5. **Notes:** plain textarea, optional.
6. **Save button:** on success, redirect to the Dex with the new catch visible at the top (default sort is recently-caught) and a brief success toast ("Boskoop caught! 35 / 87"). This moment is the reward — make it feel good but keep it simple (a toast is enough; no confetti libraries).

All validation errors render inline. The only required field is the variety.

### 5.4 Auth screens

Stock Breeze login/register/password-reset, restyled minimally to match the app. Registration is open (no email verification required). No profile page beyond what Breeze ships; trim anything unnecessary.

---

## 6. PWA

- `manifest.json`: app name "Apple-Dex" (or similar), standalone display, portrait orientation, theme color matching the app, icons at 192/512px (generate a simple apple-themed icon; a styled SVG-to-PNG export or a plain generated icon is acceptable — do not use copyrighted imagery).
- Minimal service worker: cache the app shell (CSS/JS/icons) with a cache-first strategy; all HTML/API requests network-only. Bump the cache name on deploy (tie it to a build hash or app version). No offline functionality beyond not breaking.
- Serve over HTTPS (Forge handles certificates); geolocation and PWA install both require it.

---

## 7. Non-functional requirements

- **Performance:** Dex grid must stay fast with the full catalog; eager-load catch + media relations (avoid N+1 — verify with Laravel Debugbar or `Model::preventLazyLoading()` in non-production). Thumbnails only in the grid; full conversions only on the variety card.
- **Images:** store on the local `public` disk by default, but read the disk from config/env so switching to S3 later is a one-line change.
- **Localization:** UI copy in English, single locale, but keep strings in Laravel lang files from the start so a German translation is trivially possible later. Dates displayed in a European format (dd.mm.yyyy).
- **Security:** all routes behind auth except auth screens; policies enforced everywhere; standard Laravel CSRF; validate and authorize every Livewire action server-side.
- **Code quality:** Laravel Pint for formatting; meaningful commit-sized steps if the agent commits; no dead code or commented-out blocks in the final result.

---

## 8. Testing requirements

Write Pest feature tests covering at minimum:

1. Guest is redirected to login from all app routes.
2. Dex shows global varieties plus own customs, never another user's customs.
3. Completion counter counts correctly (own catches / visible varieties).
4. Creating a catch: minimal (variety only) and full (photo, date, location, notes) paths.
5. Duplicate catch of the same variety is rejected (both via validation and, as a safety net, the DB unique constraint).
6. Creating a custom variety inline with a catch, including rejection of names colliding (case-insensitively) with a visible existing variety.
7. Editing and deleting own catch; forbidden for another user's catch.
8. Editing/deleting own custom variety works; editing/deleting global varieties or another user's customs is forbidden.
9. Search filtering returns expected varieties.
10. Photo upload stores media and generates conversions (fake storage).

---

## 9. Implementation plan (build order)

Work in this order; each phase should leave the app in a working, testable state.

**Phase 1 — Foundation**
1. Fresh Laravel 12 project, Breeze (Livewire stack), Tailwind, Pint, Pest.
2. Migrations for `varieties` and `catches` with all constraints and indexes from §3.
3. Models, relationships, `visibleTo` scope, policies, medialibrary setup with conversions.
4. `VarietySeeder` with the curated ~70–90 variety list (§4) and local test user.
5. Tests: model constraints, policies, seeder idempotency.

**Phase 2 — Dex**
6. Dex Livewire component: grid, caught/uncaught rendering, silhouette placeholder asset, completion counter.
7. Live search + sort toggle with session persistence.
8. Tests: visibility, counter, search.

**Phase 3 — Catching**
9. New Catch form: variety autocomplete, inline custom-variety creation, already-caught short-circuit, photo upload with camera capture, date, geolocation button + location label, notes.
10. Save flow with transaction (custom variety + catch together), success toast, redirect.
11. Tests: all catch-creation paths and rejections.

**Phase 4 — Variety card & management**
12. Variety card (caught/uncaught states), edit catch, delete catch, edit/delete custom variety with confirmations.
13. Tests: edit/delete authorization matrix.

**Phase 5 — Polish & PWA**
14. Manifest, icons, service worker, mobile viewport/theme-color meta.
15. Empty states, loading states (Livewire `wire:loading`), toast styling, favicon.
16. Restyle auth pages to match.
17. Final pass: N+1 audit, Pint, full test suite green.

**Phase 6 — Deployment prep**
18. `.env.example` complete; README with local setup (herd/valet/sail agnostic: `composer install`, `npm install && npm run build`, `php artisan migrate --seed`) and Forge deploy notes (deploy script should run migrations with `--force` and `php artisan db:seed --class=VarietySeeder --force` is safe due to idempotent seeding; storage:link; queue not required — no queued jobs in this app).

---

## 10. Acceptance criteria (definition of done)

- [ ] A new user can register, see the seeded dex with 0 caught, search it, and open variety cards.
- [ ] Logging a catch with only a variety name takes ≤ 4 taps from the dex and works one-handed on a 390px viewport.
- [ ] Logging with photo (via phone camera), auto-date, geolocation, and notes works end to end.
- [ ] A variety can be caught at most once per user; attempting again routes to the existing catch.
- [ ] Custom varieties can be created inline, edited, and deleted, and are invisible to other users.
- [ ] Caught tiles show the user's photo; uncaught tiles are visually distinct (greyed silhouette/reference).
- [ ] Completion counter is accurate per user.
- [ ] App is installable to the home screen (valid manifest, service worker registered, HTTPS).
- [ ] Full Pest suite passes; no lazy-loading violations; Pint clean.
- [ ] No external paid services or API keys are required anywhere in the codebase.
