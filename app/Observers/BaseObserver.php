<?php

namespace App\Observers;

use App\Enums\MorphType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Turns an observer into an audit trail for its model. Subclasses can still do
 * work of their own — see UserObserver freeing media on delete.
 *
 * Two modes. By default the observed model is the subject and its own lifecycle
 * is logged. Override isPivot() and the row is treated as a link: the activity
 * goes against parent() as attached/detached, because nobody looks up an
 * api_key_permissions row — they open the API key and ask who granted it.
 *
 * `old`/`attributes` hold the two sides of a change; `meta` carries values for
 * the message, which the UI derives from (log_name, event). `description` is
 * only the plain-English fallback stored with the row.
 */
abstract class BaseObserver
{
    /** The subject's module code, e.g. 'users'. */
    abstract protected function logName(): string;

    /** Log against parent() as attached/detached instead of against the row itself. */
    protected function isPivot(): bool
    {
        return false;
    }

    /**
     * Never logged, whatever model they are on. ignored() is additive; this is the
     * backstop, so a new observer cannot leak a secret by forgetting to name it.
     */
    protected function neverLog(): array
    {
        return [
            'password',
            'password_confirmation',
            'remember_token',
            'hash',
            'secret',
            'token',
            'api_key',
        ];
    }

    /** Attributes to leave out of this model's diffs. */
    protected function ignored(): array
    {
        return [];
    }

    /**
     * Columns to snapshot into a `deleted` row — otherwise only meta() survives the
     * record. neverLog()/ignored() still win over this.
     */
    protected function loggedOnDelete(): array
    {
        return [];
    }

    /** Values the UI interpolates into the message (e.g. :name). */
    protected function meta(Model $model): array
    {
        return [];
    }

    /** Pivot mode: what the activity is recorded against. */
    protected function parent(Model $pivot): ?Model
    {
        return null;
    }

    /** Pivot mode: what is being attached or detached. */
    protected function related(Model $pivot): ?Model
    {
        return null;
    }

    public function created(Model $model): void
    {
        if ($this->isPivot()) {
            $this->recordPivot($model, 'attached');

            return;
        }

        $this->record($model, 'created', ['attributes' => $this->clean($model, $model->getAttributes())]);
    }

    public function updated(Model $model): void
    {
        // A pivot carries only its two keys: changing a side is a detach plus an attach.
        if ($this->isPivot()) {
            return;
        }

        $attributes = Arr::except($this->clean($model, $model->getChanges()), $this->housekeeping($model));

        // Nothing loggable changed — a password-only update, or a plain touch.
        if ($attributes === []) {
            return;
        }

        $old = $this->clean($model, Arr::only($model->getOriginal(), array_keys($attributes)));

        $this->record($model, 'updated', ['old' => $old, 'attributes' => $attributes]);
    }

    public function deleted(Model $model): void
    {
        if ($this->isPivot()) {
            $this->recordPivot($model, 'detached');

            return;
        }

        $this->record($model, 'deleted', $this->deleteSnapshot($model));
    }

    /** loggedOnDelete() as an `attributes` payload. A delete has no `old` side. */
    protected function deleteSnapshot(Model $model): array
    {
        $keys = $this->loggedOnDelete();

        if ($keys === []) {
            return [];
        }

        $attributes = $this->clean($model, Arr::only($model->getAttributes(), $keys));

        return $attributes === [] ? [] : ['attributes' => $attributes];
    }

    /** Columns whose change is not news — timestamps move on every write. */
    protected function housekeeping(Model $model): array
    {
        return array_values(array_filter([
            $model->getCreatedAtColumn(),
            $model->getUpdatedAtColumn(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    protected function record(Model $subject, string $event, array $properties = []): void
    {
        activity($this->logName())
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties + ['meta' => $this->meta($subject)])
            ->log($event);
    }

    protected function recordPivot(Model $pivot, string $event): void
    {
        $parent = $this->parent($pivot);

        // Parent already gone (cascading delete): nothing to record against.
        if ($parent === null) {
            return;
        }

        $related = $this->related($pivot);

        // Its own meta, so record() keeps the related name.
        $this->record($parent, $event, [
            'related_id' => $related?->getKey(),
            'related_type' => MorphType::aliasFor($related?->getMorphClass()),
            'meta' => ['name' => $this->label($related)],
        ]);
    }

    protected function label(?Model $related): ?string
    {
        return $related?->getAttribute('name') ?? $related?->getAttribute('code');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function clean(Model $model, array $attributes): array
    {
        return $this->inSchemaOrder(
            $model,
            Arr::except($attributes, array_merge($this->neverLog(), $this->ignored())),
        );
    }

    /**
     * Order a payload by column order, i.e. migration order — a diff then reads like
     * the record. Sorted alphabetically it opens on `azure_ad_id` and buries `id`.
     * Baked in at write time: an audit row keeps the order the table had that day.
     */
    protected function inSchemaOrder(Model $model, array $attributes): array
    {
        $ordered = [];

        foreach ($this->columnOrder($model) as $column) {
            if (array_key_exists($column, $attributes)) {
                $ordered[$column] = $attributes[$column];
            }
        }

        // Union, so appends and cast-only keys survive at the end.
        return $ordered + $attributes;
    }

    /** Column names per table, resolved once per request. */
    protected static array $columnOrder = [];

    /**
     * @return array<int, string>
     */
    protected function columnOrder(Model $model): array
    {
        $table = $model->getTable();

        if (! array_key_exists($table, static::$columnOrder)) {
            try {
                static::$columnOrder[$table] = Schema::connection($model->getConnectionName())
                    ->getColumnListing($table);
            } catch (Throwable) {
                // No schema to read: leave the order alone rather than lose the diff.
                static::$columnOrder[$table] = [];
            }
        }

        return static::$columnOrder[$table];
    }
}
