<?php

namespace App\Services\Legacy\Importers;

use App\Models\Calendar;
use App\Services\Legacy\ContentImporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Academic calendars — a title, a date range and the PDF itself.
 *
 * `starts_at`/`ends_at` were datetimes in the old schema and are plain dates
 * here, which is the honest type for a term: nobody ever meant "the year begins
 * at 00:00:00".
 */
class CalendarsImporter extends ContentImporter
{
    public function module(): string
    {
        return 'calendars';
    }

    public function describe(): string
    {
        return 'Academic calendars';
    }

    protected function source(): string
    {
        return 'calendars';
    }

    protected function target(): string
    {
        return Calendar::class;
    }

    protected function translated(): array
    {
        return ['title'];
    }

    protected function thumbnailCollection(): ?string
    {
        return Calendar::FILE_COLLECTION;
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

    protected function naturalKey(object $row): ?array
    {
        return ['name' => $row->name];
    }

    protected function extra(object $row, Model $model): void
    {
        $model->start_date = $row->starts_at;
        $model->end_date = $row->ends_at;
        $model->is_active = (bool) ($row->is_active ?? true);
    }
}
