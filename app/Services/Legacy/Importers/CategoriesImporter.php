<?php

namespace App\Services\Legacy\Importers;

use App\Models\Category;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * `achievement_categories` → the one flat `categories` list.
 *
 * The old app scoped categories to achievements; this one has a single
 * classification shared by every kind of content, which is why the table is
 * named for what it is rather than for its first consumer. Nothing else changes:
 * a legacy achievement keeps pointing at the same label.
 *
 * `code` is this app's stable handle and takes the legacy slug, so a category
 * renamed in the admin afterwards is still the same category.
 */
class CategoriesImporter extends ContentImporter
{
    public function module(): string
    {
        return 'categories';
    }

    public function describe(): string
    {
        return 'Achievement categories → categories';
    }

    public function dependsOn(): array
    {
        return [];
    }

    protected function source(): string
    {
        return 'achievement_categories';
    }

    protected function target(): string
    {
        return Category::class;
    }

    protected function legacyClass(): string
    {
        return 'App\Models\AchievementCategory';
    }

    protected function translated(): array
    {
        return ['title'];
    }

    protected function thumbnailCollection(): ?string
    {
        return null;
    }

    protected function hasStatus(): bool
    {
        return false;
    }

    protected function hasSlug(): bool
    {
        return false;
    }

    protected function naturalKey(object $row): ?array
    {
        return ['code' => $this->slug($row->slug ?? $row->name ?? null, $row->id)];
    }

    protected function extra(object $row, Model $model): void
    {
        $model->code = $this->slug($row->slug ?? $row->name ?? null, $row->id);
        $model->color = $model->color ?: Category::DEFAULT_COLOR;
    }
}
