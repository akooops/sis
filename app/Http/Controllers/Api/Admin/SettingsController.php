<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Setting\SettingData;
use App\Data\Setting\UpdateSettingData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Setting;
use App\Services\Settings\SettingResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The seeded settings catalogue. Rows come from config('settings.settings')
 * through the seeder, so there is no store() and no destroy(): an admin changes
 * a `value` and nothing else, which is the whole shape of the module.
 *
 * Its neighbour is TranslationKeysController — a registry that paginates like a
 * table but is written by code, not by CRUD.
 */
class SettingsController extends ApiController
{
    public function __construct(protected SettingResolver $resolver) {}

    public function index(): JsonResponse
    {
        $settings = QueryBuilder::for(Setting::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('group'),
                AllowedFilter::exact('type'),
                $this->search(['id', 'group', 'key', 'name']),
            ])
            ->allowedSorts(['id', 'group', 'key', 'name', 'order', 'created_at'])
            // Catalogue order: the page is read section by section, and `order`
            // only means anything inside its group.
            ->defaultSort('group', 'order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        $this->resolver->hydrate($settings->getCollection());

        return $this->respond(SettingData::collect($settings, PaginatedDataCollection::class), 'Settings retrieved successfully');
    }

    /**
     * The groups the catalogue covers — drives the page's group filter.
     *
     * Read from the table, not from config: the seeder deliberately keeps a row
     * whose entry has left config, so a config-derived list would hide a group
     * whose settings still exist and are still editable.
     */
    public function groups(): JsonResponse
    {
        $groups = Setting::query()
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->all();

        return $this->respond($groups, 'Setting groups retrieved successfully');
    }

    public function show(Setting $setting): JsonResponse
    {
        $this->resolver->hydrate(new Collection([$setting]));

        return $this->respond(SettingData::from($setting), 'Setting retrieved successfully');
    }

    /**
     * `value` and nothing else. Every other column is metadata the seeder owns,
     * so the payload never reaches them — UpdateSettingData has no other property
     * to reach them with.
     */
    public function update(UpdateSettingData $data, Setting $setting): JsonResponse
    {
        $setting->update(['value' => $data->value]);

        // Hydrate the fresh row, never the written one: `resolved` is transient,
        // and a row carrying it must not be saved again.
        $fresh = $setting->fresh();

        $this->resolver->hydrate(new Collection([$fresh]));

        return $this->respond(SettingData::from($fresh), 'Setting updated successfully');
    }
}
