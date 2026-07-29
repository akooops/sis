# SIS — Saud International Schools

Laravel 10 (PHP 8.1) + Blade/Bootstrap public site + **Svelte 5 (Svelte-4 syntax, Inertia 2)** Metronic-styled admin. This file describes the codebase as it actually is — a previous CLAUDE.md documented a full "remake" architecture that was postponed for deadline reasons; do not resurrect it.

## Architecture
- **Public website** = server-rendered Blade in `resources/views/*.blade.php` (master layout + header/footer partials, Bootstrap "Sandbox"-style theme in `public/assets`, `uil-*` icons, swiper, AOS). **The public design is frozen** — no redesigns, no Tailwind on the public side. Public controllers live at `App\Http\Controllers` (e.g. `PagesController`), fetch a `Page` row by slug + `status=published`, and render views.
- **Admin panel** = Inertia + Svelte pages in `resources/js/Pages/<Module>/{Index,Create,Edit,Show}.svelte`, Metronic 9 look via `kt-*` utility classes and `ki-*` (keenicons) icons, Tailwind-style utilities. Layout in `Pages/Layouts/AdminLayout.svelte`, shared UI in `Pages/Components` (Pagination, Sidebar, Topbar, Notifications, `Forms/Select2`, `Forms/Summernote`). Svelte files use Svelte-4 syntax (`export let`, `on:click`, `$:`) even though the package is Svelte 5 — match that.
- **Admin module anatomy** (copy an existing module, e.g. VisitServices or Facilities):
  - Controller in `App\Http\Controllers\Admin` extending `Admin\Controller` (which injects `$this->indexService` + `$this->fileService`).
  - `index()` serves BOTH the Inertia page shell and JSON (`$request->expectsJson() || hasHeader('X-Requested-With')`) with `{ items, pagination: $this->indexService->handlePagination($paginator) }`; Svelte Index pages fetch client-side.
  - FormRequests in `app/Http/Requests/Admin/<Module>/`.
  - Routes in `routes/web.php` under the `admin` prefix group, each with `check.permission:admin.<module>.<action>`; permission codes seeded in `database/seeders/PermissionsSeeder.php` (+ role assignment there).
  - Sidebar entry in `Pages/Components/Sidebar.svelte` gated by `hasPermission(...)`.
- **i18n**: DB-driven. `languages` table (en default, ar RTL) + polymorphic `translations` table via `App\Traits\Translatable` (`getTranslatableFields()`, `setTranslation()`, `getLocalTranslation()`). UI strings on the public site come from `LanguageKey` rows via `getLanguageKeyLocalTranslation('key')` (cached 1h — flush cache after seeding). Admin edits translations per model via `PATCH .../update-translation` routes; admin UI itself is English literals.
- **Files/uploads**: `File` morph model (`model_type`/`model_id`, `is_main`, path on `public` disk) via `App\Services\FileService` (`upload()`, `duplicateMediaFile()`). Convention: `file()` morphOne `is_main=1` = thumbnail/cover; `files()` morphMany `is_main=0` = gallery. Admin flows upload into `Media` + duplicate the file onto the target model. `App\Traits\HasFiles` detaches (not deletes) files on model delete.
- **Notifications** (admin bell): `Notification` model (type consts + `ICONS`) written via `App\Services\NotificationService::create*Notification()` from public controllers on new submissions.
- **Slots/booking pattern**: VisitServices → VisitTimeSlots (capacity, `remaining_capacity`/`reserved` accessors, `bulkStore` day-of-week generator) → VisitBookings, with a FullCalendar picker on the public side (`assets/libs/calendar`). The Facilities reservation system is a clone of this trio.

## Facilities module (mini-websites)
- `Facility` model: slug (path mode), `domain` (subdomain label), `status` draft/published, `theme` JSON (primary/secondary colors → CSS variable overrides), contact fields (`email`, `phone`, `whatsapp`, `socials` JSON), `logo_file_id`, translatable `title/tagline/description/content/address`.
- **Routing is dual**: the same `$facilityRoutes` closure is registered under `Route::domain('{facilityDomain}.'.config('facilities.root_domain'))` (name prefix `facility.domain.`, only when `FACILITY_ROOT_DOMAIN` env is set) AND `Route::prefix('facilities/{facilitySlug}')` (name prefix `facility.`). `ResolveFacility` middleware (`resolve.facility`) binds the published facility, shares `$facility` with views, forgets the route param, and sets `URL::defaults`. **Always build mini-site links with `facilityRoute($name, $params)`** (helpers.php) — it picks the active mode; pass `['facility' => $f]` when outside a facility request.
- Mini-site templates in `resources/views/facility/` (own master layout + partials reusing the public asset stack; per-facility colors injected as a `:root` style block). Reservation flow = `facility/reserve.blade.php` (FullCalendar + Vue 3 CDN, same as `visits.blade.php`).
- **Content scoping**: `pages`, `articles`, `albums`, `events`, `menus`, `contact_submissions` have nullable `facility_id`. `NULL = main website`. Main-site queries use the `main()` scope (`whereNull('facility_id')`) — keep this invariant when adding queries, or facility content leaks onto the main site. `getMenu()` is main-site-only. Admin Create/Edit forms have a "Facility" select; Index pages a facility filter (`facility_id` query param, `'main'` sentinel for main-site-only).
- Reservations: `facility_time_slots` + `facility_reservations` (name/email/phone/guests_count/message, no status column — admins handle via email/WhatsApp quick actions on the reservation Show page, then delete). Contact messages reuse `contact_submissions` with `facility_id`.
- Content seeding: `FacilitiesSeeder` (5 facilities, EN content generated from client docs into `database/seeders/data/facilities/*.html`, facilities landing Page, `facility_*` LanguageKeys EN+AR). Arabic facility content is pending from the client — enter via admin translations.

## Conventions & gotchas
- PHP 8.1: `match` is fine; no `readonly` classes.
- Models are `$guarded = ['id']` — mass assignment silently drops non-column keys, which is why controllers pass `$request->validated()` straight to `create()`. Translatable fields are NOT columns; they live in `translations`.
- `//Properties`, `//Relationships`, `//Accessors & Mutators` comment sections in models — follow the file you're in.
- Slugs are globally unique per table (not per facility) — keep it that way to avoid route ambiguity.
- Public forms: intl-tel-input (E164 normalization repeated server-side in `prepareForValidation`), Google reCAPTCHA v2 on contact-style forms (`App\Rules\ReCaptcha`), `App\Rules\CheckInternationalPhoneNumber`.
- `getLanguageKeyLocalTranslation`/`getMenu`/`getSetting` are cached (1h) — `php artisan cache:clear` after seeding or edits.
- Admin JSON list endpoints paginate with distinct page-name params (`'article'`, `'page'`, ...) — copy whatever the module you're cloning uses.

## Build / run
`npm run build` (Vite + Svelte) and `php artisan serve`. DB migrations + `php artisan db:seed` (idempotent seeders using `updateOrCreate`). `.env`: `FACILITY_ROOT_DOMAIN` enables wildcard-subdomain facility routing (e.g. `sis.sa` → `hive.sis.sa`); leave empty to serve facilities only under `/facilities/{slug}`.
