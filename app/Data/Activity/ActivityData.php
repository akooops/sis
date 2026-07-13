<?php

namespace App\Data\Activity;

use App\Models\Activity;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an activity-log entry.
 */
class ActivityData extends Data
{
    public function __construct(
        public string $id,
        public ?string $log_name,
        public string $description,
        public ?string $event,
        public ?string $subject_type,
        public ?string $subject_id,
        public ?string $causer_type,
        public ?string $causer_id,
        public array $properties,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Activity $activity): self
    {
        return new self(
            id: $activity->id,
            log_name: $activity->log_name,
            description: $activity->description,
            event: $activity->event,
            subject_type: $activity->subject_type,
            subject_id: $activity->subject_id,
            causer_type: $activity->causer_type,
            causer_id: $activity->causer_id,
            properties: $activity->properties?->toArray() ?? [],
            created_at: $activity->created_at?->toIso8601String(),
            updated_at: $activity->updated_at?->toIso8601String(),
        );
    }
}
