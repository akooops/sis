<?php

namespace App\Observers;

use App\Enums\MorphType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Turns an observer into an audit trail for its model.
 *
 * Subclasses can still do real work of their own — see UserObserver, which frees
 * media back into the pool when a user is deleted.
 *
 * Two modes. By default the observed model is the subject and its own lifecycle
 * is logged (created/updated/deleted). Override isPivot() to true and
 * the observed row is treated as a link between two models: the activity is
 * recorded against the parent() as attached/detached, because nobody looks up an
 * api_key_permissions row — they look at the API key and ask who gave it that
 * permission, which is what the per-record activity drawer reads.
 *
 * Properties follow the activity log's own shape: `old` and `attributes` hold
 * the two sides of a change, and `meta` carries values for the message the UI
 * renders. The message itself is derived client-side from (log_name, event);
 * `description` is only the plain-English fallback stored with the row.
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
     * Attributes that must never reach the log, whatever model they are on.
     * The per-observer ignored() list is additive — this is the backstop, so a
     * new observer cannot leak a secret by forgetting to name it.
     *
     * @return array<int, string>
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

    /**
     * Attributes to leave out of this model's diffs.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return [];
    }

    /**
     * Attributes to snapshot into a `deleted` row.
     *
     * A delete otherwise records only meta(), so once the record is gone its
     * details are gone with it — name the columns worth keeping in the trail.
     * neverLog()/ignored() still win, so a secret named here is never written.
     *
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return [];
    }

    /**
     * Values the UI interpolates into the message (e.g. :name).
     *
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return [];
    }

    /** Pivot mode: the model the activity is recorded against (the ApiKey, Role, User…). */
    protected function parent(Model $pivot): ?Model
    {
        return null;
    }

    /** Pivot mode: the thing being attached or detached (the Permission, Role…). */
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

        $this->record($model, 'created', ['attributes' => $this->clean($model->getAttributes())]);
    }

    public function updated(Model $model): void
    {
        // A pivot row only carries its two foreign keys and timestamps: changing
        // either side is a detach plus an attach, which are logged as such.
        if ($this->isPivot()) {
            return;
        }

        $attributes = Arr::except($this->clean($model->getChanges()), $this->housekeeping($model));

        // Nothing loggable actually changed — a password-only update, or a
        // plain touch. A row here would be an empty diff.
        if ($attributes === []) {
            return;
        }

        $old = $this->clean(Arr::only($model->getOriginal(), array_keys($attributes)));

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

    /**
     * The loggedOnDelete() columns, as an `attributes` payload shaped like the
     * one an `updated` row carries (there is no `old` side to a delete).
     *
     * @return array<string, mixed>
     */
    protected function deleteSnapshot(Model $model): array
    {
        $keys = $this->loggedOnDelete();

        if ($keys === []) {
            return [];
        }

        $attributes = $this->clean(Arr::only($model->getAttributes(), $keys));

        return $attributes === [] ? [] : ['attributes' => $attributes];
    }

    /**
     * Columns whose change is not itself news — timestamps move on every write.
     *
     * @return array<int, string>
     */
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

        // The parent is already gone (a cascading delete): there is nothing to
        // record the activity against, and its own deletion was logged anyway.
        if ($parent === null) {
            return;
        }

        $related = $this->related($pivot);

        // Passes its own meta, so record() keeps the related name rather than
        // asking the parent for one.
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
    protected function clean(array $attributes): array
    {
        return Arr::except($attributes, array_merge($this->neverLog(), $this->ignored()));
    }
}
