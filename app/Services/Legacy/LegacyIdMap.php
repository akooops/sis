<?php

namespace App\Services\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * legacy integer id => the ULID row it became.
 *
 * THIS IS WHAT MAKES THE IMPORT RE-RUNNABLE, and the reason it is a table rather
 * than an in-memory array: a run that dies on module nine must not orphan the
 * eight that succeeded, and a mapping corrected next week must repair the rows
 * it already wrote rather than duplicate them.
 *
 * It also settles the ordering problem. Menu items link to pages, banners link
 * to pages, grades belong to programmes, and a facility's articles are a pivot —
 * none of which can be resolved by natural key, because a grade is only "the
 * name of a grade inside a programme" and two programmes may both have a Year 4.
 * With the map, an importer asks `find('pages', 12)` and gets the ULID whatever
 * order the modules ran in, as long as the target ran first.
 *
 * Reads are memoised per table on first use — a menu import resolving 200 items
 * against five content types would otherwise be 200 point queries.
 */
class LegacyIdMap
{
    /** @var array<string, array<int, array{type: class-string, id: string}>> */
    protected array $cache = [];

    /**
     * Record that a legacy row became this model.
     *
     * `updateOrInsert` on the unique pair rather than insert: re-running must
     * re-point the mapping at whatever the importer resolved this time, which is
     * what lets a row deleted by hand in the admin be recreated cleanly.
     */
    public function put(string $source, int $legacyId, Model $model): void
    {
        DB::table('legacy_imports')->updateOrInsert(
            ['source' => $source, 'legacy_id' => $legacyId],
            [
                'model_type' => $model->getMorphClass(),
                'model_id' => $model->getKey(),
                'updated_at' => now(),
                // Only set on insert in spirit; updateOrInsert writes both, and a
                // created_at that tracks the latest run is more useful here than
                // one frozen at the first.
                'created_at' => now(),
                'id' => (string) Str::ulid(),
            ],
        );

        $this->cache[$source][$legacyId] = [
            'type' => $model->getMorphClass(),
            'id' => (string) $model->getKey(),
        ];
    }

    /**
     * The ULID a legacy row became, or null.
     *
     * Null is an ordinary answer, not an error: a menu item may point at a page
     * whose module was excluded from this run with --only, and the caller decides
     * whether that means "skip" or "render an unlinked label".
     */
    public function find(string $source, int|string|null $legacyId): ?string
    {
        if ($legacyId === null || $legacyId === '' || ! is_numeric($legacyId)) {
            return null;
        }

        return $this->load($source)[(int) $legacyId]['id'] ?? null;
    }

    /** Resolve a legacy row straight to a live model instance. */
    public function findModel(string $source, int|string|null $legacyId): ?Model
    {
        $entry = $this->load($source)[(int) ($legacyId ?: 0)] ?? null;

        if (! $entry || ! class_exists($entry['type'])) {
            return null;
        }

        /** @var class-string<Model> $class */
        $class = $entry['type'];

        return $class::find($entry['id']);
    }

    /** Whether a legacy row has already been imported. */
    public function has(string $source, int $legacyId): bool
    {
        return isset($this->load($source)[$legacyId]);
    }

    /**
     * Existing rows for one legacy table, read once.
     *
     * @return array<int, array{type: class-string, id: string}>
     */
    protected function load(string $source): array
    {
        if (isset($this->cache[$source])) {
            return $this->cache[$source];
        }

        $rows = DB::table('legacy_imports')
            ->where('source', $source)
            ->get(['legacy_id', 'model_type', 'model_id']);

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->legacy_id] = [
                'type' => $row->model_type,
                'id' => (string) $row->model_id,
            ];
        }

        return $this->cache[$source] = $map;
    }

    /**
     * Drop mappings whose target no longer exists.
     *
     * Someone deleting an imported article in the admin leaves a mapping pointing
     * at nothing; without this, the next run would consider it imported and skip
     * it forever. Called once at the start of a run.
     *
     * @return int The number of stale mappings removed.
     */
    public function prune(): int
    {
        $removed = 0;

        $types = DB::table('legacy_imports')->distinct()->pluck('model_type');

        foreach ($types as $type) {
            if (! class_exists($type)) {
                $removed += DB::table('legacy_imports')->where('model_type', $type)->delete();

                continue;
            }

            /** @var class-string<Model> $type */
            $table = (new $type)->getTable();

            $removed += DB::table('legacy_imports')
                ->where('model_type', $type)
                ->whereNotExists(fn ($q) => $q
                    ->select(DB::raw(1))
                    ->from($table)
                    ->whereColumn($table.'.id', 'legacy_imports.model_id'))
                ->delete();
        }

        $this->cache = [];

        return $removed;
    }
}
