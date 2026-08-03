<?php

namespace App\Services\Settings;

use App\Enums\MorphType;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Turns the ids a model setting stores into the labels a page can show.
 *
 * Cost is one query per DISTINCT model type on the page, never one per row: the
 * page is grouped by model_type, every referenced id in the group is collected
 * (single values and arrays alike), and the whole group is fetched in one
 * whereKey. Twelve settings pointing at media are one query; resolving row by
 * row would be twelve, and a multi-valued one would be worse again.
 *
 * Labels reach the row through Setting::withResolved(), which is transient — so
 * hydrate AFTER writing a row, never before, or a save would carry `resolved`
 * into the update as a column that does not exist.
 *
 * A reference whose record is gone resolves to a visible TOMBSTONE rather than
 * being dropped. "Not set" and "points at a deleted article" look identical once
 * an entry vanishes, and they ask opposite things of the admin: one wants a
 * value chosen, the other wants the setting repointed or the record restored.
 * Dropping it would also silently shorten the list it belongs to, so a menu of
 * five featured articles would quietly become four.
 */
class SettingResolver
{
    /**
     * Hang [{id, label, missing}, ...] on every model setting in the collection.
     * Other types are left alone — their `resolved` stays absent, which is what
     * the DTO reports as null.
     */
    public function hydrate(Collection $settings): void
    {
        $referencing = $settings->filter(fn (Setting $setting) => $setting->type === 'model');

        if ($referencing->isEmpty()) {
            return;
        }

        // Cast because an array key of '' is what a null alias groups under, and a
        // row whose alias left the registry still has to resolve to tombstones
        // rather than disappear from the page.
        foreach ($referencing->groupBy(fn (Setting $setting) => (string) $setting->model_type) as $alias => $group) {
            $labels = $this->labels((string) $alias, $this->ids($group));

            foreach ($group as $setting) {
                $setting->withResolved($this->pairs((string) $alias, $setting, $labels));
            }
        }
    }

    /**
     * The ids one setting points at, in the order it stores them — that order is
     * editorial for something like featured articles, so it is not the query's to
     * decide.
     *
     * Read off the value's SHAPE rather than is_multiple: a setting flipped in
     * config still holds whatever the admin last saved.
     *
     * @return array<int, string>
     */
    protected function referenced(Setting $setting): array
    {
        $ids = [];

        foreach (is_array($setting->value) ? $setting->value : [$setting->value] as $id) {
            if (is_scalar($id) && (string) $id !== '') {
                $ids[] = (string) $id;
            }
        }

        return $ids;
    }

    /**
     * Every distinct id one model type is asked for across the page.
     *
     * @return array<int, string>
     */
    protected function ids(Collection $settings): array
    {
        return $settings
            ->flatMap(fn (Setting $setting) => $this->referenced($setting))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * id => label for one model type, in one query.
     *
     * Whole rows, not a two-column select: `label` is whatever the registry names
     * it, it may be a translatable column whose accessor needs the cast, and a
     * field that is not a column at all has to degrade to an id rather than turn
     * a settings page into a SQL error.
     *
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    protected function labels(string $alias, array $ids): array
    {
        $class = MorphType::classFor($alias);

        if ($class === null || $ids === []) {
            return [];
        }

        $field = Setting::modelConfig($alias)['label'] ?? 'name';

        /** @var Model $model */
        $model = new $class;

        return $model->newQuery()
            ->whereKey($ids)
            ->get()
            ->mapWithKeys(fn (Model $record) => [(string) $record->getKey() => $this->label($record, $field)])
            ->all();
    }

    /**
     * A record with a blank label still has to render as something: an
     * untranslated title or an unnamed row would otherwise be an empty chip that
     * nobody can click off. The id is ugly but it identifies.
     */
    protected function label(Model $record, string $field): string
    {
        $label = $record->getAttribute($field);

        return is_scalar($label) && trim((string) $label) !== ''
            ? (string) $label
            : (string) $record->getKey();
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<int, array{id: string, label: string, missing: bool}>
     */
    protected function pairs(string $alias, Setting $setting, array $labels): array
    {
        // The registry's own display name, so the tombstone says what is missing.
        // Falls back to the raw alias, then to a word, for a setting whose model
        // left config — the row is still on the page and still needs a label.
        $name = Setting::modelConfig($alias)['name'] ?? ($alias !== '' ? $alias : 'record');

        return array_map(fn (string $id) => [
            'id' => $id,
            'label' => $labels[$id] ?? "Missing {$name} ({$id})",
            'missing' => ! array_key_exists($id, $labels),
        ], $this->referenced($setting));
    }
}
