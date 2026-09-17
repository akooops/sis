<?php

namespace App\Services\Legacy\Importers;

use App\Models\Event;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Events. `starts_at`/`ends_at` are spelled `start_at`/`end_at` here — the one
 * rename in this table, and both are NOT NULL, so a legacy row missing either is
 * reported rather than written with a zero date.
 *
 * The old Event had a gallery alongside its thumbnail; this app's Event has only
 * a thumbnail. Those photos are NOT deleted — they stay in the media library as
 * free media, exactly as they arrived — but nothing links them to the event any
 * more, which the run reports.
 */
class EventsImporter extends ContentImporter
{
    protected int $galleries = 0;

    public function module(): string
    {
        return 'events';
    }

    public function describe(): string
    {
        return 'Events';
    }

    protected function source(): string
    {
        return 'events';
    }

    protected function target(): string
    {
        return Event::class;
    }

    protected function thumbnailCollection(): ?string
    {
        return Event::THUMBNAIL_COLLECTION;
    }

    protected function thumbnailIsMainOnly(): bool
    {
        return true;
    }

    protected function extra(object $row, Model $model): void
    {
        $model->start_at = $row->starts_at;
        $model->end_at = $row->ends_at ?: $row->starts_at;
    }

    protected function after(object $row, Model $model): void
    {
        if (! $this->c->db->has('files')) {
            return;
        }

        $this->galleries += $this->c->db->table('files')
            ->where('model_type', $this->legacyClass())
            ->where('model_id', $row->id)
            ->where('is_main', 0)
            ->count();
    }

    public function run(): void
    {
        parent::run();

        if ($this->galleries > 0) {
            $this->c->note(
                "{$this->galleries} event gallery photo(s) are in the media library but not linked to an event — "
                .'this app gives an event a thumbnail only. Create an album if they should stay together.'
            );
        }
    }
}
