<?php

namespace App\Http\Controllers\Api;

use App\Enums\MorphType;
use App\Http\Controllers\Controller;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Base for the JSON API controllers. The { status, message, data } envelope
 * comes from Controller::respond(); everything here is a piece of the shared
 * query contract the index endpoints expose through spatie/query-builder —
 * filter[field], filter[search], sort=field|-field, include=rel, per_page, page.
 */
abstract class ApiController extends Controller
{
    protected int $defaultPerPage = 15;

    protected int $maxPerPage = 100;

    /**
     * Safe page size from ?per_page=…, clamped so a client can show fewer or
     * more rows without being able to request the whole table.
     */
    protected function perPage(): int
    {
        $perPage = (int) request()->integer('per_page', $this->defaultPerPage);

        return max(1, min($perPage, $this->maxPerPage));
    }

    /**
     * A general "search" filter (?filter[search]=…) that matches the given
     * columns with a LIKE, independent of the per-field filters. The model's
     * primary key is always matched too, so pasting an id (or its clipped #head)
     * finds the record.
     *
     * @param  array<int, string>  $columns
     */
    protected function search(array $columns, bool $withId = true): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) use ($columns, $withId) {
            $query->where(function ($query) use ($columns, $withId, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$value}%");
                }
                if ($withId) {
                    $query->orWhere($query->getModel()->getKeyName(), 'like', "%{$value}%");
                }
            });
        });
    }

    /**
     * A "search" filter that also reaches into spatie/laravel-translatable JSON
     * columns, matching every locale passed in — so searching a page finds it by
     * its Arabic title as readily as by its slug.
     *
     * The CAST is load-bearing, not decoration. MySQL's json_unquote() hands back
     * a utf8mb4_bin string, so a plain LIKE against it is CASE-SENSITIVE while the
     * same LIKE on `name` is not: searching "home" would match the slug and
     * silently miss a page titled "Home". CAST(… AS CHAR) re-tags the value with
     * the connection collation — utf8mb4_unicode_ci here, the same one the tables
     * use — which makes translated search behave like every other search in the
     * app rather than nearly like it. A missing locale key yields NULL, and
     * NULL LIKE … is NULL, so absent translations simply don't match.
     *
     * Cost: a JSON column cannot carry a plain index, so this is a table scan.
     * Fine for a CMS; if it ever isn't, the fix is an indexed generated column
     * per (column, locale), not a change here.
     *
     * @param  array<int, string>  $columns  plain columns
     * @param  array<int, string>  $translated  JSON (translatable) columns
     * @param  array<int, string>  $locales  locale codes to match, e.g. ['en', 'ar']
     */
    protected function searchTranslations(array $columns, array $translated, array $locales, bool $withId = true): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) use ($columns, $translated, $locales, $withId) {
            // One nested group, so the whole thing ANDs with the other filters
            // instead of widening them — same shape as search().
            $query->where(function ($query) use ($columns, $translated, $locales, $withId, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$value}%");
                }

                $grammar = $query->getQuery()->getGrammar();

                foreach ($translated as $column) {
                    // Wrapped by the grammar rather than interpolated: the column
                    // list is developer-supplied, and the JSON path is a binding.
                    $wrapped = $grammar->wrap($column);

                    foreach ($locales as $locale) {
                        $query->orWhereRaw(
                            "cast(json_unquote(json_extract({$wrapped}, ?)) as char) like ?",
                            ['$."'.$locale.'"', "%{$value}%"],
                        );
                    }
                }

                if ($withId) {
                    $query->orWhere($query->getModel()->getKeyName(), 'like', "%{$value}%");
                }
            });
        });
    }

    /**
     * An exact filter (?filter[<name>]=<id>) that matches records related to a
     * given id through a relationship — e.g. users by role id, roles by
     * permission id, or (in domain modules) students by their guardian id.
     */
    protected function searchRelationById(string $name, string $relation): AllowedFilter
    {
        return AllowedFilter::callback($name, function ($query, $value) use ($relation) {
            $query->whereHas($relation, fn ($related) => $related->whereKey($value));
        });
    }

    /**
     * A "search" filter that matches columns on a related model (?filter[search]=…),
     * e.g. searching a pivot by its permission's code/name.
     *
     * @param  array<int, string>  $columns
     */
    protected function searchRelationByColumns(string $relation, array $columns): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) use ($relation, $columns) {
            $query->whereHas($relation, function ($query) use ($columns, $value) {
                $query->where(function ($query) use ($columns, $value) {
                    foreach ($columns as $column) {
                        $query->orWhere($column, 'like', "%{$value}%");
                    }
                });
            });
        });
    }

    /**
     * Filter a polymorphic column by its public alias.
     * An unknown alias matches nothing rather than being ignored — a filter the
     * server doesn't understand must never widen the result set.
     */
    protected function morphType(string $name): AllowedFilter
    {
        return AllowedFilter::callback($name, function ($query, $value) use ($name) {
            $query->where($name, MorphType::classFor($value) ?? '-');
        });
    }

    /**
     * A date-boundary filter (?filter[<name>]=2024-01-01). The name is public
     * only — created_from and created_to bound the same column from either side,
     * which is why the two are separate filters rather than one: spatie keys
     * allowed filters BY NAME, so registering the same name twice silently keeps
     * only one of them. An unparsable date is a 422: a date the server can't read
     * must not silently drop the filter.
     *
     * `$column` defaults to created_at, which is what every module wanted until a
     * record gained dates of its own (an event's start_at). Pass it explicitly and
     * the public filter name no longer has to match the column.
     */
    protected function date(string $name, string $operator, string $boundary, string $column = 'created_at'): AllowedFilter
    {
        return AllowedFilter::callback($name, function ($query, $value) use ($name, $operator, $boundary, $column) {
            try {
                $date = Carbon::parse($value)->{$boundary}();
            } catch (InvalidFormatException) {
                throw ValidationException::withMessages([$name => __('validation.date', ['attribute' => $name])]);
            }

            $query->where($column, $operator, $date);
        });
    }
}
