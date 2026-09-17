<?php

namespace App\Services\Legacy\Importers;

use App\Models\Brand;
use App\Models\BrandAsset;
use App\Models\BrandAssetGroup;
use App\Services\Legacy\ContentImporter;

/**
 * Brand pages and their downloadable assets.
 *
 * THE ONE STRUCTURAL CHANGE: the old app kept an asset's grouping as a STRING
 * COLUMN on the asset ("documents", "logos"), so a group had no identity — it
 * could not be renamed, reordered or translated, and a typo made a second group.
 * This app gives it a row. So the import reads the distinct values of that
 * column per brand and creates a `brand_asset_groups` row for each, in first-seen
 * order, then points the assets at it.
 *
 * The group is mapped under a synthetic source (`brand_asset_groups`) keyed on
 * the ASSET's own legacy id for the first asset that named it — there is no
 * legacy row to key on, and a re-run has to find the same group rather than make
 * another. Keyed that way, it does.
 */
class BrandsImporter extends ContentImporter
{
    /** brand ULID => legacy group name => BrandAssetGroup */
    protected array $groups = [];

    public function module(): string
    {
        return 'brands';
    }

    public function describe(): string
    {
        return 'Brands, asset groups and assets';
    }

    public function sources(): array
    {
        return ['brands'];
    }

    protected function source(): string
    {
        return 'brands';
    }

    protected function target(): string
    {
        return Brand::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return Brand::THUMBNAIL_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    public function run(): void
    {
        parent::run();

        $this->assets();
    }

    protected function assets(): void
    {
        $this->each('brand_assets', function (object $row) {
            $brandId = $this->c->map->find('brands', $row->brand_id);

            if (! $brandId) {
                $this->c->warn("Brand asset [{$row->name}] belongs to a brand that was not imported — skipped.");
                $this->c->skipped();

                return;
            }

            $group = $this->group($brandId, (string) ($row->group ?: 'documents'), (int) $row->id);

            $model = $this->model(BrandAsset::class, 'brand_assets', (int) $row->id);

            $model->name = (string) $row->name;
            $model->order = (int) ($row->order ?? 0);
            $model->brand_asset_group_id = $group->id;
            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'brand_assets', (int) $row->id);

            if (! $this->c->dryRun) {
                $this->c->files->attach(
                    $this->legacyFileId('App\Models\BrandAsset', (int) $row->id),
                    $model,
                    BrandAsset::FILE_COLLECTION,
                );
            }
        });
    }

    /**
     * The group row for a legacy group NAME, made once per brand.
     *
     * Memoised per run and mapped by the id of the first asset that mentioned it,
     * so a second run reuses the same row instead of creating "Documents" twice.
     */
    protected function group(string $brandId, string $name, int $firstAssetId): BrandAssetGroup
    {
        if (isset($this->groups[$brandId][$name])) {
            return $this->groups[$brandId][$name];
        }

        $group = BrandAssetGroup::query()
            ->where('brand_id', $brandId)
            ->where('name', $name)
            ->first();

        if (! $group) {
            $group = new BrandAssetGroup;
            $group->name = $name;
            $group->brand_id = $brandId;
            $group->order = count($this->groups[$brandId] ?? []);

            if (! $this->c->dryRun) {
                $group->save();
                $this->c->map->put('brand_asset_groups', $firstAssetId, $group);
            }

            $this->c->created();
        }

        return $this->groups[$brandId][$name] = $group;
    }

    /** The legacy `files` row a legacy model owned, if any. */
    protected function legacyFileId(string $legacyClass, int $legacyId): ?int
    {
        $file = $this->c->db->table('files')
            ->where('model_type', $legacyClass)
            ->where('model_id', $legacyId)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first(['id']);

        return $file ? (int) $file->id : null;
    }
}
