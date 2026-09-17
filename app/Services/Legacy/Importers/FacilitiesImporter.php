<?php

namespace App\Services\Legacy\Importers;

use App\Models\Facility;
use App\Models\FacilitySlot;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Facilities: the venue, its bookable times, and the content attached to it.
 *
 * ── THE INVERSION ────────────────────────────────────────────────────────────
 *
 * The old app made a facility a MINI-SITE: it had its own domain, its own theme
 * JSON, its own logo and contact details, and articles belonged to it through a
 * nullable `articles.facility_id`. That column is the thing this app deliberately
 * removed — it meant every main-site query had to remember a `->main()` scope it
 * could silently forget.
 *
 * Here a facility is a venue, and content is ATTACHED to it through a real pivot
 * with its own key. So `articles.facility_id` becomes a `facility_articles` row
 * and the article keeps its own URL and its own listing — which is also why the
 * import does not have to decide whether a facility's article is "really" a news
 * article: it is one, and it is also attached to a venue.
 *
 * Everything that made it a mini-site has nowhere to go and is reported: domain,
 * theme, logo, its own email/phone/WhatsApp/socials, plus the translated tagline
 * and address. `facility_id` on pages, events and menus is dropped for the same
 * reason — those were per-site chrome, and there is one site now.
 */
class FacilitiesImporter extends ContentImporter
{
    /**
     * The logo file id read off the row in extra(), attached in after() — the
     * model has to exist before anything can be given to it.
     */
    protected ?int $pendingLogo = null;

    public function module(): string
    {
        return 'facilities';
    }

    public function describe(): string
    {
        return 'Facilities, their time slots and attached content';
    }

    public function dependsOn(): array
    {
        return ['files', 'articles', 'albums'];
    }

    public function sources(): array
    {
        return ['facilities'];
    }

    protected function source(): string
    {
        return 'facilities';
    }

    protected function target(): string
    {
        return Facility::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return Facility::THUMBNAIL_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function hasOrder(): bool
    {
        return true;
    }

    protected function extra(object $row, Model $model): void
    {
        /*
         * The old logo lived on a `logo_file_id` column rather than the morph, so
         * it is the one file in the whole migration that is found by reading a
         * column. It becomes the thumbnail only when the row has no morph file of
         * its own — a venue photo is the better thumbnail, and the logo would
         * otherwise silently replace it.
         */
        if (! empty($row->logo_file_id) && ! $this->hasOwnFile($row)) {
            $this->pendingLogo = (int) $row->logo_file_id;
        }
    }

    protected function after(object $row, Model $model): void
    {
        if ($this->pendingLogo !== null) {
            $this->c->files->attach($this->pendingLogo, $model, Facility::THUMBNAIL_COLLECTION);
            $this->pendingLogo = null;
        }

        $this->slots($row, $model);
        $this->attachContent($row, $model);
    }

    /** `facility_time_slots` → `facility_slots`. */
    protected function slots(object $row, Model $model): void
    {
        $this->each('facility_time_slots', function (object $slot) use ($model) {
            // Same duplicate-window fold as the visits side — `facility_slots`
            // carries the same unique index the legacy table lacked.
            $item = $this->slot(FacilitySlot::class, 'facility_time_slots', $slot, 'facility_id', $model->id);

            $this->save($item, 'facility_time_slots', (int) $slot->id);
        }, fn ($query) => $query->where('facility_id', $row->id));
    }

    /**
     * `articles.facility_id` / `albums.facility_id` → the pivots.
     *
     * insertOrIgnore on the unique pair, because a re-attach must be a no-op
     * rather than a constraint violation — the same shape the admin's own pivot
     * endpoints use.
     */
    protected function attachContent(object $row, Model $model): void
    {
        foreach ([
            ['articles', 'facility_articles', 'article_id'],
            ['albums', 'facility_albums', 'album_id'],
        ] as [$legacyTable, $pivot, $column]) {
            if (! $this->c->db->has($legacyTable)) {
                continue;
            }

            if (! $this->c->db->hasColumn($legacyTable, 'facility_id')) {
                continue;
            }

            $ids = $this->c->db->table($legacyTable)
                ->where('facility_id', $row->id)
                ->pluck('id');

            foreach ($ids as $legacyId) {
                $newId = $this->c->map->find($legacyTable, $legacyId);

                if (! $newId) {
                    continue;
                }

                DB::table($pivot)->insertOrIgnore([
                    'id' => (string) Str::ulid(),
                    'facility_id' => $model->id,
                    $column => $newId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /** Whether the legacy row owned a file through the morph. */
    protected function hasOwnFile(object $row): bool
    {
        return $this->c->db->has('files') && $this->c->db->table('files')
            ->where('model_type', $this->legacyClass())
            ->where('model_id', $row->id)
            ->where('is_main', 1)
            ->exists();
    }

    public function run(): void
    {
        parent::run();

        // Same nullOnDelete orphan the visits side has; see
        // VisitServicesImporter::reportOrphanSlots().
        if ($this->c->db->has('facility_time_slots')) {
            $orphans = $this->c->db->table('facility_time_slots')->whereNull('facility_id')->count();

            if ($orphans > 0) {
                $this->c->note(
                    "{$orphans} legacy time slot(s) belong to no facility and were skipped — a slot here must name "
                    .'the venue it is for.'
                );
            }
        }

        foreach (['pages', 'events', 'menus'] as $table) {
            if (! $this->c->db->hasColumn($table, 'facility_id')) {
                continue;
            }

            $scoped = $this->c->db->table($table)->whereNotNull('facility_id')->count();

            if ($scoped > 0) {
                $this->c->note(
                    "{$scoped} {$table} row(s) were scoped to a facility in the old app and are now main-site rows — "
                    .'this app attaches articles and albums to a venue, and nothing else.'
                );
            }
        }
    }
}
