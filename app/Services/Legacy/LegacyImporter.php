<?php

namespace App\Services\Legacy;

use App\Services\Phone\PhoneFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Throwable;

/**
 * One module's worth of migration.
 *
 * A module is a thing an operator would name — "articles", "visits", "menus" —
 * not a table, because several legacy tables can make up one answer (programmes
 * carry their streams and grades) and one legacy table can feed two modules.
 * `--only` and `--except` speak in these names.
 *
 * THE CONTRACT EVERY IMPORTER KEEPS:
 *
 *  - It is IDEMPOTENT. Running it twice leaves the database as it was after the
 *    first run. Every write goes through save(), which reconciles against the
 *    id map rather than inserting blind.
 *  - It never deletes anything it did not create.
 *  - A legacy value it cannot carry is REPORTED, never dropped silently. That is
 *    the whole difference between a migration you can sign off and one you have
 *    to spot-check forever.
 *  - It depends only on modules listed in dependsOn(), which is what fixes the
 *    run order — menu items cannot resolve a page that has not been imported.
 */
abstract class LegacyImporter
{
    /**
     * The context is optional ONLY so that `legacy:import --list` can ask every
     * importer for its name, description and dependencies without a database
     * connection to a legacy install that may not exist yet. Nothing else may be
     * called on a context-less instance.
     */
    public function __construct(protected ?LegacyContext $c = null) {}

    /** The name `--only`/`--except` use. */
    abstract public function module(): string;

    /** What this module writes, for the run plan. */
    abstract public function describe(): string;

    /** Modules that must run first. */
    public function dependsOn(): array
    {
        return [];
    }

    /** Legacy tables this importer reads. Absent tables make it a no-op. */
    abstract public function sources(): array;

    abstract public function run(): void;

    /**
     * Whether the legacy database can answer this module at all.
     *
     * A dump from an older install genuinely may predate `brands` or
     * `facilities`; that is a fact about the data, not a failure, so the command
     * reports it and moves on.
     */
    public function available(): bool
    {
        foreach ($this->sources() as $table) {
            if (! $this->c->db->has($table)) {
                return false;
            }
        }

        return true;
    }

    /* -----------------------------------------
     Reading the legacy side
    ------------------------------------------*/

    /**
     * Walk a legacy table in id order.
     *
     * CHUNKED because these tables are unbounded — an install with 40,000 form
     * submissions would otherwise hydrate all of them before writing the first
     * row. Ordered by id so a run that dies part-way resumes deterministically.
     */
    protected function each(string $table, callable $handler, ?callable $filter = null): void
    {
        if (! $this->c->db->has($table)) {
            return;
        }

        $query = $this->c->db->table($table)->orderBy('id');

        if ($filter) {
            $filter($query);
        }

        $query->chunk(500, function ($rows) use ($handler) {
            foreach ($rows as $row) {
                $handler($row);
            }
        });
    }

    /** Translations for a legacy model class, loaded once. */
    protected function translationsFor(string $legacyClass): array
    {
        return $this->c->translations->for($legacyClass);
    }

    /* -----------------------------------------
     Writing this app's side
    ------------------------------------------*/

    /**
     * Find-or-make the model a legacy row maps to.
     *
     * The id map is consulted FIRST and a natural key only as a fallback, because
     * the map is the authority: a slug the admin has since edited must still
     * resolve to the row it edited, not create a second one under the old slug.
     */
    protected function model(string $class, string $source, int $legacyId, ?array $naturalKey = null): Model
    {
        $id = $this->c->map->find($source, $legacyId);

        if ($id && $found = $class::find($id)) {
            return $found;
        }

        if ($naturalKey) {
            $found = $class::query()->where($naturalKey)->first();

            if ($found) {
                return $found;
            }
        }

        return new $class;
    }

    /**
     * Persist and record the mapping.
     *
     * Counting happens here rather than at each call site so the report cannot
     * disagree with what was written — `exists` is read before the save, which is
     * the only moment it distinguishes a create from an update.
     */
    protected function save(Model $model, string $source, int $legacyId): Model
    {
        $isNew = ! $model->exists;

        if ($this->c->dryRun) {
            $isNew ? $this->c->created() : $this->c->updated();

            return $model;
        }

        // Nothing changed: skip the write so the row keeps its own updated_at and
        // no observer fires. A re-run over 5,000 unchanged articles should be
        // quiet, not 5,000 no-op updates.
        if (! $isNew && ! $model->isDirty()) {
            $this->c->skipped();
            $this->c->map->put($source, $legacyId, $model);

            return $model;
        }

        $model->save();

        $this->c->map->put($source, $legacyId, $model);

        $isNew ? $this->c->created() : $this->c->updated();

        return $model;
    }

    /**
     * Assign a JSON column only when its CONTENT actually changed.
     *
     * MySQL's native JSON type stores an object in a normalised form, with keys
     * sorted rather than in the order they were written — the same fact that
     * forced SubmissionValidator::snapshot() to carry an explicit integer
     * `order`. Eloquent's isDirty() compares the two sides as ENCODED STRINGS, so
     * a value read back from the database and re-encoded in insertion order never
     * matches, and the row is dirty on every run forever.
     *
     * The visible symptom is a re-run reporting "updated" for rows nothing
     * touched — which quietly destroys the one property this whole import is
     * built on. Compared decoded, and key-order-insensitively, instead.
     */
    protected function setJson(Model $model, string $attribute, array $value): void
    {
        $current = $model->{$attribute};

        if (is_array($current) && $this->sameJson($current, $value)) {
            return;
        }

        $model->{$attribute} = $value;
    }

    /** Deep equality that ignores the order of string keys. */
    protected function sameJson(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($a as $key => $value) {
            if (! array_key_exists($key, $b)) {
                return false;
            }

            $other = $b[$key];

            if (is_array($value) && is_array($other)) {
                if (! $this->sameJson($value, $other)) {
                    return false;
                }

                continue;
            }

            if ($value !== $other) {
                return false;
            }
        }

        return true;
    }

    /**
     * Fill a UNIQUE identifying column without stealing it from another row.
     *
     * ── TWO OF THIS APP'S OWN RULES PULL AGAINST EACH OTHER HERE ────────────
     *
     * `Candidate::scopeIdentifiedBy` and `Visitor::scopeIdentifiedBy` match on
     * EMAIL **OR** PHONE — that is what makes a family who booked with one
     * address and the hall with another one household, and it is verified
     * behaviour. But `email` and `phone` are each UNIQUE on both tables.
     *
     * Live, those coexist: one submission at a time, and a collision is rare
     * enough to surface as a failed request. Replayed over years of history they
     * collide hard. Real data: 40 phone numbers appear against two or more
     * different email addresses, and 30 addresses against two or more numbers —
     * agencies applying on behalf of several people, families sharing a line,
     * and plain typos. So a row matched BY PHONE would have its email
     * overwritten with one a DIFFERENT candidate already owns, and the import
     * died on a duplicate-key violation a third of the way in.
     *
     * The rule here: an identifying value already on file is never overwritten,
     * and a value another row owns is never taken. First writer wins, which is
     * also what makes the import idempotent — two legacy rows cannot take turns
     * rewriting the same column on every run.
     *
     * A refused value is not lost: the other legacy columns still land, and the
     * clash is counted for the report, because it means two records that may be
     * two different people were merged into one.
     */
    protected function identify(Model $model, string $column, ?string $value): void
    {
        $value = trim((string) $value);

        if ($value === '' || (string) $model->{$column} === $value) {
            return;
        }

        // Already has one. Keep it — the person is identified either way, and
        // rewriting is what causes both the collision and the churn.
        if (filled($model->{$column})) {
            $this->c->identityClashes++;

            return;
        }

        $query = $model->newQuery()->where($column, $value);

        if ($model->exists) {
            $query->whereKeyNot($model->getKey());
        }

        if ($query->exists()) {
            $this->c->identityClashes++;

            return;
        }

        $model->{$column} = $value;
    }

    /**
     * Keep the EARLIEST legacy timestamp when several legacy rows fold onto one.
     *
     * A min rather than an assignment, for the reason slot() spells out: two
     * folded rows carry two different created_at values, so assigning makes each
     * rewrite the other on every run and the import reports hundreds of
     * "updated" rows forever.
     */
    protected function earliest(Model $model, mixed $legacyCreatedAt): void
    {
        if ($legacyCreatedAt === null) {
            return;
        }

        $model->created_at = $model->created_at === null
            ? $legacyCreatedAt
            : min($model->created_at, Carbon::parse($legacyCreatedAt));
    }

    /**
     * A bookable slot, folding a legacy duplicate onto the row that already
     * holds that time window.
     *
     * THE OLD TABLES HAD NO UNIQUE INDEX AND THIS APP'S DO. `visit_slots` is
     * unique on (service, starts_at, ends_at) and `facility_slots` on
     * (facility, starts_at, ends_at) — deliberately, since that is what makes
     * the admin's duplicate-slot 422 possible. The legacy tables allowed the
     * same window twice, and real data is full of it: a bulk generator that was
     * run a second time over a term leaves a complete duplicate set.
     *
     * Two legacy rows describing one window ARE one slot here, so the second is
     * reconciled onto the first rather than inserted. BOTH LEGACY IDS ARE THEN
     * MAPPED ONTO THAT ONE ROW, which is the part that matters: a booking made
     * against either legacy slot still resolves, and two bookings that were on
     * separate duplicates land on the same slot where `unique(slot, visitor)`
     * can see them.
     *
     * Capacity takes the HIGHER of the two. It is a claim about how many
     * bookings the window accepts, and honouring the larger one cannot turn an
     * existing booking into an over-capacity one — which the admin calendar
     * would then ring for a person to resolve.
     */
    protected function slot(string $class, string $source, object $row, string $ownerColumn, string $ownerId): Model
    {
        $model = $this->model($class, $source, (int) $row->id);

        if (! $model->exists) {
            $existing = $class::query()
                ->where($ownerColumn, $ownerId)
                ->where('starts_at', $row->starts_at)
                ->where('ends_at', $row->ends_at)
                ->first();

            if ($existing) {
                $model = $existing;
                $this->c->collapsedSlots++;
            }
        }

        $model->{$ownerColumn} = $ownerId;
        $model->starts_at = $row->starts_at;
        $model->ends_at = $row->ends_at;
        $model->capacity = max((int) $model->capacity, max(1, (int) ($row->capacity ?? 1)));

        // The old schema had no open/closed flag; every slot it held was one it
        // was willing to take a booking on. Never re-opened on a later run — that
        // is an admin's decision once the data is here.
        $model->is_open = $model->exists ? $model->is_open : true;

        // The earliest of the folded rows — see earliest().
        $this->earliest($model, $row->created_at ?? null);

        return $model;
    }

    /* -----------------------------------------
     Value translation
    ------------------------------------------*/

    /**
     * A publish status this app recognises.
     *
     * Both apps spell the three the same way, so this is a guard rather than a
     * map: an unknown value becomes a draft, because publishing something the
     * import did not understand is the one failure mode with a public blast
     * radius.
     */
    protected function status(?string $legacy, array $allowed = ['draft', 'published', 'hidden']): string
    {
        $legacy = strtolower(trim((string) $legacy));

        return in_array($legacy, $allowed, true) ? $legacy : 'draft';
    }

    /**
     * When a published row went live.
     *
     * The old schema had no such column, so the row's own creation is the best
     * evidence available — and it has to be SOMETHING, because a published row
     * with a null published_at reads as "published, date unknown" everywhere the
     * admin sorts by it.
     */
    protected function publishedAt(object $row, string $status): ?string
    {
        return $status === 'published' ? ($row->created_at ?? null) : null;
    }

    /** A URL-safe slug, falling back to the legacy id so it is never empty. */
    protected function slug(?string $value, string|int $fallback): string
    {
        $slug = Str::slug((string) $value);

        return $slug !== '' ? $slug : 'item-'.$fallback;
    }

    /**
     * Split a single legacy name column into the two this app stores.
     *
     * The old forms asked for "Guardian name" as one box; candidates, visitors
     * and attendees all have first/last here. The LAST whitespace-separated word
     * is taken as the surname, which is right for the overwhelming majority of
     * both English and Arabic names in this data, and a one-word name becomes a
     * first name with an empty surname rather than being refused.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitName(?string $full): array
    {
        $parts = preg_split('/\s+/u', trim((string) $full), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return ['Unknown', ''];
        }

        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $last = array_pop($parts);

        return [implode(' ', $parts), $last];
    }

    /**
     * A phone in the shape this app stores and dedupes on.
     *
     * E164, via the app's own formatter, because Candidate and Visitor are both
     * matched on "email OR phone" and a number that did not go through the same
     * normaliser would never match — which would silently make every imported
     * person a second record for someone who is already here.
     *
     * The app's formatter refuses to guess a region, so a LOCAL legacy number
     * (`0500000033`) comes back null from it. `--phone-region` is the operator
     * saying which country this install's history is from; without it the number
     * is kept verbatim and counted, because it is still the only number anyone
     * has for that person — but the run says how many, since each one is a
     * person dedup may fail to recognise.
     */
    protected function phone(?string $raw): ?string
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        if ($e164 = PhoneFormatter::e164($raw)) {
            return $e164;
        }

        if ($region = $this->c?->phoneRegion) {
            try {
                $util = PhoneNumberUtil::getInstance();
                $parsed = $util->parse($raw, $region);

                if ($util->isValidNumber($parsed)) {
                    return $util->format($parsed, PhoneNumberFormat::E164);
                }
            } catch (Throwable) {
                // Fall through to keeping it verbatim.
            }
        }

        $this->c->rawPhones++;

        return $raw;
    }

    /** An enum value through config('legacy.enums'). */
    protected function enum(string $group, ?string $value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));
        $value = config("legacy.enums.{$group}.{$value}", $value);

        if (in_array($value, $allowed, true)) {
            return $value;
        }

        if ($value !== '') {
            $this->c->warn("Unknown {$group} [{$value}] — stored as [{$default}].");
        }

        return $default;
    }

    /**
     * A legacy polymorphic class name as this app's.
     *
     * Returns null for a type this app dropped, which callers render as an
     * unlinked label — a menu is chrome and must never break a page.
     */
    protected function morph(?string $legacyClass): ?string
    {
        if (! is_string($legacyClass) || $legacyClass === '') {
            return null;
        }

        $map = config('legacy.morphs', []);

        if (array_key_exists($legacyClass, $map)) {
            return $map[$legacyClass];
        }

        return class_exists($legacyClass) ? $legacyClass : null;
    }

    /**
     * LEGACY CLASS => the legacy table it was imported from.
     *
     * KEYED ON THE LEGACY SIDE, not on this app's class, and that matters: two
     * legacy tables can land in one destination. `newsletters` and `forms` are
     * BOTH documents here, so a reverse lookup from Document could only ever
     * return one of them and every menu item pointing at the other would resolve
     * to the wrong row — or, more likely, to nothing.
     *
     * Only the classes that can actually appear in a polymorphic column need an
     * entry; anything absent becomes an unlinked label.
     */
    protected const LEGACY_SOURCES = [
        'App\Models\Page' => 'pages',
        'App\Models\Article' => 'articles',
        'App\Models\Album' => 'albums',
        'App\Models\Event' => 'events',
        'App\Models\Achievement' => 'achievements',
        'App\Models\AchievementCategory' => 'achievement_categories',
        'App\Models\Program' => 'programs',
        'App\Models\ProgramStream' => 'program_streams',
        'App\Models\Grade' => 'grades',
        'App\Models\JobPosting' => 'job_postings',
        'App\Models\Facility' => 'facilities',
        'App\Models\Brand' => 'brands',
        'App\Models\Newsletter' => 'newsletters',
        'App\Models\Form' => 'forms',
        'App\Models\VisitService' => 'visit_services',
        'App\Models\Partner' => 'partners',
        'App\Models\Calendar' => 'calendars',
    ];

    /** The legacy table a legacy class's rows were imported from. */
    protected function sourceFor(string $legacyClass): ?string
    {
        return static::LEGACY_SOURCES[$legacyClass] ?? null;
    }

    /** Report the legacy columns this module has nowhere to put. */
    protected function reportDropped(string $legacyTable): void
    {
        $dropped = config("legacy.dropped.{$legacyTable}", []);

        if ($dropped !== []) {
            $this->c->note(
                "`{$legacyTable}` columns with no destination in this app: ".implode(', ', $dropped).'.'
            );
        }
    }
}
