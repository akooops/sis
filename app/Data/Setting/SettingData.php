<?php

namespace App\Data\Setting;

use App\Models\Setting;
use Spatie\LaravelData\Data;

/**
 * One row of the seeded catalogue. Everything but `value` is metadata the seeder
 * owns from config — it rides down anyway because the form renders its control
 * from `type`, `is_multiple`, `model`, `model_type` and `options`, and a second
 * request for the registry would just be the same row again.
 *
 * `model` is the row's own entry from config('settings.models'), not just its
 * alias: the picker needs a route to search and a column to show, and shipping
 * them with the row is what keeps that list config rather than a map on the
 * client that goes stale the day a model becomes pickable.
 *
 * `resolved` holds the labels behind a model setting's ids, [{id, label,
 * missing}, ...] in the order the value stores them, and is null for every other
 * type. It is read off the transient attribute SettingResolver::hydrate() sets:
 * a DTO must never query, or one page of settings becomes one query per row.
 */
class SettingData extends Data
{
    public function __construct(
        public string $id,
        public string $group,
        public string $key,
        public string $type,
        public bool $is_multiple,
        public ?string $model_type,
        /** @var array<string, mixed>|null */
        public ?array $model,
        /** @var array<int, array<string, mixed>>|null */
        public ?array $options,
        /** @var array<string, mixed>|null */
        public ?array $filter,
        public string $name,
        public ?string $description,
        public mixed $value,
        /** @var array<int, array{id: string, label: string, missing: bool}>|null */
        public ?array $resolved,
        public int $order,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Setting $setting): self
    {
        $resolved = $setting->getAttribute('resolved');

        return new self(
            id: $setting->id,
            group: $setting->group,
            key: $setting->key,
            type: $setting->type,
            is_multiple: $setting->is_multiple,
            model_type: $setting->model_type,
            // The alias's config('settings.models') entry, so the picker reads its
            // route and label column off the row. Null for every other type, and
            // for an alias that has left the registry — which is exactly what the
            // form degrades to a plain id box on.
            model: Setting::modelConfig($setting->model_type),
            options: $setting->options,
            // The picker sends this to the target's index endpoint verbatim, and
            // App\Rules\SettingReference applies the same array server-side.
            filter: $setting->filter,
            name: $setting->name,
            description: $setting->description,
            value: $setting->value,
            // Absent rather than empty when the row was never hydrated, so an
            // unresolved model setting is not mistaken for one pointing nowhere.
            resolved: is_array($resolved) ? $resolved : null,
            order: $setting->order,
            created_at: $setting->created_at?->toIso8601String(),
            updated_at: $setting->updated_at?->toIso8601String(),
        );
    }
}
