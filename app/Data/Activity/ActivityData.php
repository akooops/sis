<?php

namespace App\Data\Activity;

use App\Enums\MorphType;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

/**
 * subject_type/causer_type are MorphType aliases, never class names.
 * The UI builds its message from (log_name, event); description is the
 * stored English fallback.
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

    /** Users have no single name column; a deleted record resolves to null. */
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
