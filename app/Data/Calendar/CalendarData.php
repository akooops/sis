<?php

namespace App\Data\Calendar;

use App\Models\Calendar;
use Spatie\LaravelData\Data;

class CalendarData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $start_date,
        public ?string $end_date,
        public bool $is_active,
        /** @var array<string, string|null> */
        public array $title,
        public ?string $file_url,
        public ?string $file_name,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Calendar $calendar): self
    {
        $file = $calendar->getMedia(Calendar::FILE_COLLECTION)->first();

        return new self(
            id: $calendar->id,
            name: $calendar->name,
            start_date: $calendar->start_date?->toDateString(),
            end_date: $calendar->end_date?->toDateString(),
            is_active: $calendar->is_active,
            title: $calendar->enabledTranslations('title'),
            file_url: $calendar->file_url,
            file_name: $file?->name,
            created_at: $calendar->created_at?->toIso8601String(),
            updated_at: $calendar->updated_at?->toIso8601String(),
        );
    }
}
