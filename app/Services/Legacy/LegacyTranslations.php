<?php

namespace App\Services\Legacy;

/**
 * The old app's `translations` table, read into the shape this app stores.
 *
 * Two completely different designs for the same idea. The old app kept one ROW
 * per (model, field, language) in a polymorphic side table; this one keeps a
 * JSON column per translatable attribute, `{"en": "...", "ar": "..."}`, read by
 * spatie/laravel-translatable. So the whole job here is a pivot: gather every
 * row for one legacy model type and hand each importer a
 * `[legacyId][field] => [locale => value]` map it can splat onto a model.
 *
 * LOADED PER TYPE, ONCE. A content import touching 800 articles across four
 * translated fields and nine locales is ~29,000 legacy rows; fetching them per
 * article would be 800 queries returning 36 rows each.
 *
 * ── The placeholder trap ─────────────────────────────────────────────────────
 * The old Translatable trait returned the literal string "title.ar" when a
 * translation was missing, AND its admin form prefilled inputs with that same
 * return value — so saving a form that was never translated WROTE "title.ar"
 * into the table as though it were content. Those rows are indistinguishable
 * from real data by type, only by shape, and carrying them across would put
 * `description.fr` on the public French site. They are dropped here, which is
 * the one place that has both the field name and the locale to recognise one.
 */
class LegacyTranslations
{
    /** @var array<string, array<int, array<string, array<string, string>>>> */
    protected array $cache = [];

    /** @var array<int, string>|null legacy language id => locale code */
    protected ?array $locales = null;

    public function __construct(
        protected LegacyDatabase $db,
        /**
         * An absolute site URL to strip out of translated HTML.
         *
         * The old editor inserted images as absolute links to whatever hostname
         * the admin was on. Since the import PRESERVES each file's name, the path
         * half of those links is still correct — `/storage/uploads/<uuid>.jpg`
         * resolves in the new app exactly as it did in the old one — so stripping
         * the host is all that is needed to make a whole body of content portable.
         * Rewriting the paths themselves is what we are deliberately not doing.
         */
        protected ?string $stripHost = null,
    ) {}

    /**
     * Every translation for one legacy model class.
     *
     * @return array<int, array<string, array<string, string>>>
     */
    public function for(string $legacyClass): array
    {
        if (isset($this->cache[$legacyClass])) {
            return $this->cache[$legacyClass];
        }

        if (! $this->db->has('translations')) {
            return $this->cache[$legacyClass] = [];
        }

        $locales = $this->locales();
        $out = [];

        $this->db->table('translations')
            ->where('translatable_type', $legacyClass)
            ->orderBy('id')
            ->chunk(2000, function ($rows) use (&$out, $locales) {
                foreach ($rows as $row) {
                    $locale = $locales[(int) $row->language_id] ?? null;
                    $value = $this->clean($row->value, $row->field, $locale);

                    if ($locale === null || $value === null) {
                        continue;
                    }

                    $out[(int) $row->translatable_id][$row->field][$locale] = $value;
                }
            });

        return $this->cache[$legacyClass] = $out;
    }

    /**
     * One model's translations, ready to be assigned.
     *
     * Restricted to `$fields` so an importer names the attributes it is about to
     * write — a legacy field this app dropped (a facility's `tagline`) must not
     * arrive as an attribute the model has no column for, which Eloquent would
     * reject at save time with a message about the column and not the import.
     *
     * @param  array<int, string>  $fields
     * @return array<string, array<string, string>>
     */
    public function pick(array $all, int $legacyId, array $fields): array
    {
        $row = $all[$legacyId] ?? [];
        $out = [];

        foreach ($fields as $legacyField => $column) {
            // A plain list means the names match on both sides.
            if (is_int($legacyField)) {
                $legacyField = $column;
            }

            if (! empty($row[$legacyField])) {
                $out[$column] = $row[$legacyField];
            }
        }

        return $out;
    }

    /**
     * The value as this app should store it, or null to drop the row.
     *
     * Drops the "field.locale" placeholders described in the class docblock, and
     * empty strings — which are worse than absent under this app's
     * `fill_missing_keys = false` rule: Laravel falls back to another locale only
     * when a key is MISSING, never when it is present and blank, so an empty
     * string renders as an empty heading to a visitor.
     */
    protected function clean(?string $value, string $field, ?string $locale): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === "{$field}.{$locale}") {
            return null;
        }

        if ($this->stripHost !== null) {
            $value = str_replace(
                [$this->stripHost.'/', $this->stripHost],
                ['/', ''],
                $value,
            );
        }

        return $value;
    }

    /**
     * legacy language id => locale code, with config('legacy.locales') applied.
     *
     * @return array<int, string>
     */
    protected function locales(): array
    {
        if ($this->locales !== null) {
            return $this->locales;
        }

        $map = config('legacy.locales', []);
        $out = [];

        foreach ($this->db->table('languages')->get(['id', 'code']) as $row) {
            $code = $map[$row->code] ?? $row->code;
            $out[(int) $row->id] = $code;
        }

        return $this->locales = $out;
    }
}
