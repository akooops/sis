# SIS — Novonordisk Supply Chain (chore/remake)

Laravel 10 + Inertia 2 + **Svelte 5 (runes)** + **Metronic 9 (Tailwind 4, built from source)**. This file is the source of truth for a fresh session — read it first.

## Architecture
- **Decoupled JSON API + thin Inertia shell.** Controllers return the envelope `{ status, message, data }` (paginated `data = { data, meta, links }`); pages are Svelte and fetch client-side via the shared API client (NOT `Inertia::render` for data — web routes only render page shells).
- Query contract (spatie/query-builder): `filter[field]`, `filter[search]`, `filter[trashed]=with|only`, `sort=field|-field`, `include=rel`, `per_page`, `page`.
- Auth: custom Sanctum-session + Azure SSO, approval gate (`verified_at`). Single upload route `POST /api/v1/uploads` → forms submit only the returned media `id`.
- i18n: Laravel PHP lang files, namespaced `lang/{en,ar}/{admin,user}/*`, shared via `HandleInertiaRequests` (`i18n` prop) → Svelte `$t()`/`$dir`. Also shares `media` (upload rules) + `auth`. RTL via `dir`; locale switch = `POST web.locale.set`.

## Frontend layout (`resources/js/`)
- `lib/` — `api/{client,useIndex.svelte,useForm.svelte}`, `i18n`, `date`, `format`, `permissions`, `upload`, `kt` (KTUI init), `menu` (sidebar config), `portal`, `toast`, `confirm`.
- `components/{ui,form,data,media,feedback,layout}` — the deduplicated kit. Key: `IndexCard` (Metronic list card + fly-in create/edit swap), `DataTable` (sortable, skeleton, `kt-table`), `Filters` (config-driven drawer incl. `resource-select`), `Drawer`/`Modal` (Svelte-driven, portaled), `Dropdown` (portaled — avoids table overflow clipping), `Select` (async combobox = Select2 replacement), `PhoneInput` (intl-tel-input), `PasswordInput`, `DatePicker` (flatpickr), `MediaPicker`/`MediaLibraryModal`.
- `layouts/{AdminLayout,AuthLayout}`, `Pages/Admin/{Users,Roles,Permissions,ApiKeys,Media}` + `Pages/Auth/Login` + `Pages/Home`.

## Conventions (match these)
- **Svelte 5 runes only** — `$props/$state/$derived/$effect`, callback props, snippets. No `export let`/`createEventDispatcher`/`on:`.
- Preserve the **Metronic demo1 look** (`kt-*` classes). Icons = **keenicons** (`ki-filled ki-*`).
- A module Index = `AdminLayout` + `IndexCard` with `toolbar`/`form`/`table` snippets; create/edit = **fly-in card swap**; view/filters/pivots = **right-side drawers**; row actions = portaled `Dropdown`.
- New module i18n keys go in `lang/{en,ar}/admin/<module>.php`; use `$t('<module>.…')` + `$t('common.…')`.

## Gotchas (already solved — don't reintroduce)
- npm `@keenthemes/ktui` has **no `KTMenu`** → sidebar accordion/collapse are **Svelte-driven**, not `data-kt-menu`. KTUI (dropdowns/toggle/sticky/theme-switch) is init via `lib/kt.js` `initKt()` in `app.js` + `AdminLayout`.
- **Keenicons** must be imported in `app.js` (unlayered), NOT `@import`-ed into `app.css`, or Tailwind's `::before` reset wipes the glyphs.
- Don't put `kt-drawer` on the Svelte `Drawer` (its base CSS mis-positions it). `#app` needs `flex-grow:1;width:100%` (in `app.css`).
- KTUI widgets in client-rendered content (form panels, drawers) won't be re-init'd → use Svelte-native equivalents (`PasswordInput`, portaled `Dropdown`).

## Status
- **Done + build-verified:** foundations, full kit, all 5 backed modules (Users/Roles/Permissions/ApiKeys/Media) on the Metronic standard, auth (Login only — forgot/reset removed; admin resets passwords), i18n/RTL, media library, sidebar (dark + brand primary). Login + admin chrome verified live.
- **Not runtime-verified:** the authed module pages end-to-end (need `php artisan migrate --seed`, then log in as the seeded super user). `ENABLE_PERMISSIONS=false` in dev.
- **Deferred:** the ~26 orphaned domain modules (Products, PurchaseOrders, Warehouse, …) were deleted (recoverable from git) — rebuild on this same standard when their backends exist.

## Build / run
`npm run build` (or `npm run dev`) + `php artisan serve`. Metronic source is in `metronic/`; Tailwind scans `resources/js/**` (arbitrary utility classes work). No jQuery. Detailed history + rationale: `~/.claude/plans/audit-the-app-folder-elegant-wreath.md` and the `remake-context-artifacts` memory.
