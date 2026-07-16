<?php

namespace App\Data\Activity;

use App\Enums\MorphType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * Output DTO for an activity-log entry.
 *
 * `subject_type`/`causer_type` are short aliases (App\Enums\MorphType), never
 * the stored class names — clients shouldn't filter on a namespace we might
 * refactor. The UI builds its message from (log_name, event) and interpolates
 * `properties.meta`; `description` is the stored English fallback for anything
 * it has no translation for.
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
        public ?string $subject_label,
        public ?string $causer_type,
        public ?string $causer_id,
        public ?string $causer_name,
        public array $properties,
        public ?string $batch_uuid,
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
            subject_type: MorphType::aliasFor($activity->subject_type),
            subject_id: $activity->subject_id,
            subject_label: static::label($activity->subject),
            causer_type: MorphType::aliasFor($activity->causer_type),
            causer_id: $activity->causer_id,
            causer_name: static::label($activity->causer),
            properties: $activity->properties?->toArray() ?? [],
            batch_uuid: $activity->batch_uuid,
            created_at: $activity->created_at?->toIso8601String(),
            updated_at: $activity->updated_at?->toIso8601String(),
        );
    }

    /**
     * A human label for either end of the morph. Users have no single `name`
     * column, and a deleted or force-deleted record resolves to null — the UI
     * falls back to the id it already has.
     */
    protected static function label(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

        if ($model instanceof User) {
            return trim("{$model->firstname} {$model->lastname}") ?: $model->username;
        }

        return $model->getAttribute('name')
            ?? $model->getAttribute('code')
            ?? null;
    }
}
