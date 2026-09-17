<?php

namespace App\Services\Legacy\Importers;

use App\Models\Partner;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Partner logos.
 *
 * `partners.url` is NOT NULL here and was nullable in the old app — a partner
 * with no link falls back to '#', which is what the old site rendered anyway
 * through its logo-only markup, rather than being refused at save time.
 */
class PartnersImporter extends ContentImporter
{
    public function module(): string
    {
        return 'partners';
    }

    public function describe(): string
    {
        return 'Partner logos';
    }

    protected function source(): string
    {
        return 'partners';
    }

    protected function target(): string
    {
        return Partner::class;
    }

    protected function translated(): array
    {
        return [];
    }

    protected function thumbnailCollection(): ?string
    {
        return Partner::LOGO_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function hasSlug(): bool
    {
        return false;
    }

    /**
     * The legacy table had no order column at all — partners came back in id
     * order. `created_at` is preserved by the import and is this app's tiebreak,
     * so leaving every row at 0 reproduces the old ordering exactly.
     */
    protected function hasOrder(): bool
    {
        return false;
    }

    protected function naturalKey(object $row): ?array
    {
        return ['name' => $row->name];
    }

    protected function extra(object $row, Model $model): void
    {
        $model->url = $row->url ?: '#';
    }
}
