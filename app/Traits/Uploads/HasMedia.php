<?php

namespace App\Traits\Uploads;

use App\Models\Media;
use App\States\Media\Clean;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Gives a model media collections. A collection is just a label on the media row
 * — nothing about it is reflected on disk — so attaching and detaching are plain
 * database writes (see App\Services\Uploads\UploadService).
 *
 * Only scanned files are ever returned: an infected or not-yet-scanned upload
 * must never be linked from the UI.
 */
trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /** @return Collection<int, Media> */
    public function getMedia(string $collection): Collection
    {
        return $this->media()
            ->where('collection_name', $collection)
            ->whereState('state', Clean::class)
            ->orderBy('order_column')
            ->orderBy('created_at')
            ->get();
    }

    public function getFirstMedia(string $collection): ?Media
    {
        return $this->getMedia($collection)->first();
    }

    public function getFirstMediaUrl(string $collection): ?string
    {
        return $this->getFirstMedia($collection)?->url;
    }

    /**
     * Collections that hold at most one file: attaching to one of these frees
     * whatever was there before. Override per model.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [];
    }
}
