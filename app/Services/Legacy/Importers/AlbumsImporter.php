<?php

namespace App\Services\Legacy\Importers;

use App\Models\Album;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Photo albums, thumbnail and gallery.
 *
 * The old app split the two by `is_main` on a single morphMany; this app splits
 * them by collection name. The gallery keeps the old ordering, which was file id
 * — the order they were uploaded in, and the order the old site displayed.
 */
class AlbumsImporter extends ContentImporter
{
    public function module(): string
    {
        return 'albums';
    }

    public function describe(): string
    {
        return 'Photo albums and their galleries';
    }

    protected function source(): string
    {
        return 'albums';
    }

    protected function target(): string
    {
        return Album::class;
    }

    protected function translated(): array
    {
        // The old albums table had no body.
        return ['title', 'description'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Album::THUMBNAIL_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function after(object $row, Model $model): void
    {
        $this->attachGallery($row, $model, Album::FILES_COLLECTION);
    }
}
