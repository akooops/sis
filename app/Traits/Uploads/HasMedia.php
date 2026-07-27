<?php

namespace App\Traits\Uploads;

use App\Models\Media;
use App\States\Media\Clean;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Media collections for a model. A collection is just a label on the media row,
 * never anything on disk, so attach/detach are plain database writes.
 *
 * Only scanned files are returned — an infected or pending upload must never be
 * linked from the UI.
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
        // Use the eager-loaded relation when there is one. An index appends a *_url per
        // row, so filtering has to happen in PHP or ->with('media') buys nothing.
        if ($this->relationLoaded('media')) {
            return $this->media
                ->where('collection_name', $collection)
                ->filter(fn (Media $media) => $media->state instanceof Clean)
                ->sortBy([['order_column', 'asc'], ['created_at', 'asc']])
                ->values();
        }

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
     * Collections holding at most one file — attaching frees what was there.
     *
     * @return array<int, string>
     */
    public function singleFileCollections(): array
    {
        return [];
    }
}
