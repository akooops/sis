<?php

namespace App\Models;

use App\Enums\MorphType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * One configurable setting, identified by `group` and `key`. Mirrors
 * config('settings.settings'); grown by code, never CRUD — an admin edits
 * `value` and nothing else, which is why the module has no store and no
 * destroy.
 *
 * `type` names the shape of `value` and `is_multiple` says whether the row holds
 * one of them or a list, so a list of strings is text+is_multiple and a list of
 * references is model+is_multiple.
 *
 * A `model` setting stores the bare id of another record and `model_type` the
 * MorphType ALIAS it points at — never a class name, the same call MenuItem
 * makes. The labels behind those ids are resolved per page by
 * App\Services\Settings\SettingResolver, never by this model.
 */
class Setting extends Model
{
    use HasUlids;

    /* -----------------------------------------
     1. Attributes
    ------------------------------------------*/

    /**
     * The shapes a setting's value can take — the vocabulary config/settings.php
     * is written against and lib/setting.js mirrors. `is_multiple` multiplies
     * each of them rather than doubling this list.
     *
     * @var array<int, string>
     */
    public const TYPES = ['text', 'number', 'date', 'select', 'model'];

    protected $guarded = ['id'];

    /**
     * `value` is cast json and not array on purpose: the column legitimately
     * holds a scalar for a single-valued setting, and the cast name should not
     * claim otherwise. Both decode through fromJson(), so a bare string
     * round-trips identically either way.
     */
    protected $casts = [
        'value' => 'json',
        'options' => 'array',
        'filter' => 'array',
        'is_multiple' => 'bool',
        'order' => 'integer',
    ];

    /* -----------------------------------------
     2. Relationships
    ------------------------------------------*/

    /* -----------------------------------------
     3. Accessors
    ------------------------------------------*/

    /* -----------------------------------------
     4. Methods
    ------------------------------------------*/

    /** Display order: groups together, then the position declared in config. */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('group')->orderBy('order')->orderBy('key');
    }

    /**
     * The models a `model` setting may point at, keyed by MorphType alias. Read
     * through here rather than config() directly: the picker, the exists rule
     * and the resolver must all agree on the same closed list.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function models(): array
    {
        return (array) config('settings.models', []);
    }

    /**
     * One entry, or null when the alias is not in the registry — so a caller can
     * branch on the endpoint and label without first checking the alias is real.
     *
     * @return array<string, mixed>|null
     */
    public static function modelConfig(?string $alias): ?array
    {
        if ($alias === null) {
            return null;
        }

        return static::models()[$alias] ?? null;
    }

    /** The table an alias points at, for the `value` exists rule. */
    public static function linkableTable(?string $alias): ?string
    {
        $class = static::linkableClass($alias);

        return $class === null ? null : (new $class)->getTable();
    }

    /** The class an alias points at, or null when the registry does not know it. */
    public static function linkableClass(?string $alias): ?string
    {
        if (static::modelConfig($alias) === null) {
            return null;
        }

        return MorphType::classFor($alias);
    }

    /**
     * The records this setting may point at, already narrowed by its own `filter`.
     * One query behind both halves of the promise: the picker asks the index
     * endpoint with that same filter array, and App\Rules\SettingReference checks
     * the submitted id against this.
     *
     * NULL means "accept nothing", and that is deliberate. An alias the registry
     * cannot resolve, or a filter key it does not know how to apply, must reject
     * every id rather than fall back to an unfiltered query — the picker would
     * still be narrowing, so a validator that quietly widened would accept ids the
     * admin was never offered. That is the exact drift this column exists to stop,
     * so it fails closed.
     */
    public function referenceQuery(): ?Builder
    {
        $class = static::linkableClass($this->model_type);

        if ($class === null) {
            return null;
        }

        // How each filter key is applied server-side, declared once per model
        // beside the endpoint the picker sends it to.
        $applicable = static::modelConfig($this->model_type)['filters'] ?? [];

        $query = $class::query();

        foreach ((array) $this->filter as $key => $value) {
            $how = $applicable[$key] ?? null;

            if ($how === null) {
                return null;
            }

            if (isset($how['scope'])) {
                $query->{$how['scope']}($value);

                continue;
            }

            $query->where($how['column'], $value);
        }

        return $query;
    }

    /**
     * The codes a select setting accepts, taken off the same `options` the form
     * renders so the rule can never offer a choice it would then reject.
     *
     * @return array<int, mixed>
     */
    public function optionValues(): array
    {
        return array_column((array) $this->options, 'value');
    }

    /**
     * Hang the resolved labels for a model setting off the row so a paginated
     * page can go straight to SettingData. `resolved` is transient — no column,
     * never saved — so hydrate a row AFTER writing it, never before.
     *
     * @param  array<int, array<string, mixed>>  $resolved
     */
    public function withResolved(array $resolved): static
    {
        $this->setAttribute('resolved', $resolved);

        return $this;
    }
}
