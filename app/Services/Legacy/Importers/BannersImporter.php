<?php

namespace App\Services\Legacy\Importers;

use App\Models\Banner;
use App\Models\Page;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Home-page banners.
 *
 * Two shapes line up exactly and one does not. The files do: the old banner kept
 * its still as `is_main = 1` and its video as `is_main = 0` on the same relation,
 * which is precisely this app's thumbnail and video collections.
 *
 * The link does not. The old banner could only ever point at a Page, through a
 * plain `page_id`; this app lets it point at any content type through a morph.
 * A legacy `page_id` therefore becomes a Page linkable, and a banner with none
 * keeps its typed `url` — which is still how an external link is expressed.
 *
 * The old table had no status at all, so every banner was live. They are imported
 * PUBLISHED, because importing them as drafts would empty the home page's hero
 * on the first deploy and look like the migration lost them.
 */
class BannersImporter extends ContentImporter
{
    public function module(): string
    {
        return 'banners';
    }

    public function describe(): string
    {
        return 'Home-page banners (image, video and link)';
    }

    public function dependsOn(): array
    {
        return ['files', 'pages'];
    }

    protected function source(): string
    {
        return 'banners';
    }

    protected function target(): string
    {
        return Banner::class;
    }

    protected function translated(): array
    {
        return ['title', 'cta'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Banner::THUMBNAIL_COLLECTION;
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

    protected function hasOrder(): bool
    {
        return true;
    }

    protected function naturalKey(object $row): ?array
    {
        return null;
    }

    protected function extra(object $row, Model $model): void
    {
        $model->url = $row->url ?: null;

        $model->status = $model->exists ? $model->status : 'published';
        $model->published_at ??= $row->created_at ?? now();

        if ($pageId = $this->c->map->find('pages', $row->page_id ?? null)) {
            $model->linkable_type = Page::class;
            $model->linkable_id = $pageId;
        }
    }

    protected function after(object $row, Model $model): void
    {
        // The old `video()` relation: the same morph, is_main = 0.
        $video = $this->c->db->table('files')
            ->where('model_type', $this->legacyClass())
            ->where('model_id', $row->id)
            ->where('is_main', 0)
            ->orderBy('id')
            ->first(['id']);

        if ($video) {
            $this->c->files->attach($video->id, $model, Banner::VIDEO_COLLECTION);
        }
    }
}
