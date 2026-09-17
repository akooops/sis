<?php

namespace App\Services\Legacy\Importers;

use App\Models\Achievement;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Achievements. `achievement_date` is `achieved_at` here, and the category is
 * resolved through the import map rather than by name — two categories may share
 * a title across locales, and the map is the only thing that knows which row a
 * given legacy id became.
 */
class AchievementsImporter extends ContentImporter
{
    public function module(): string
    {
        return 'achievements';
    }

    public function describe(): string
    {
        return 'Achievements';
    }

    public function dependsOn(): array
    {
        return ['files', 'categories'];
    }

    protected function source(): string
    {
        return 'achievements';
    }

    protected function target(): string
    {
        return Achievement::class;
    }

    protected function translated(): array
    {
        return ['title', 'description', 'content', 'done_by'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Achievement::THUMBNAIL_COLLECTION;
    }

    protected function extra(object $row, Model $model): void
    {
        $model->achieved_at = $row->achievement_date;

        $model->category_id = $this->c->map->find('achievement_categories', $row->achievement_category_id ?? null)
            ?? $model->category_id;
    }
}
