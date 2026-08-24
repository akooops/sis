<?php

namespace App\Http\Controllers\Api;

use App\Enums\MorphType;
use App\Http\Controllers\Controller;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Base for the JSON API controllers. Everything here is a piece of the shared
 * query contract the index endpoints expose through spatie/query-builder:
 * filter[field], filter[search], sort=field|-field, include=rel, per_page, page.
 */
abstract class ApiController extends Controller
{
    protected int $defaultPerPage = 15;

    protected int $maxPerPage = 100;

    /** Page size from ?per_page, clamped so a client cannot request the whole table. */
    protected function perPage(): int
    {
        $perPage = (int) request()->integer('per_page', $this->defaultPerPage);

        return max(1, min($perPage, $this->maxPerPage));
    }

    /**
     * LIKE across the given columns, independent of the per-field filters. The
     * primary key is always matched, so pasting an id (or its #head) finds the row.
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
     * search() that also reaches into translatable JSON columns, matching every
     * locale passed in — so a page is findable by its Arabic title, not just its slug.
     *
     * The CAST is load-bearing: json_unquote() returns utf8mb4_bin, so a plain LIKE
     * against it is CASE-SENSITIVE while the same LIKE on `name` is not. CAST(… AS
     * CHAR) re-tags it with the connection collation. A missing locale yields NULL,
     * and NULL LIKE … is NULL, so absent translations simply do not match.
     *
     * Cost: JSON columns cannot be indexed, so this is a table scan. Fine for a CMS;
     * if it stops being fine, add a generated column per (column, locale).
     */
    protected function searchTranslations(array $columns, array $translated, array $locales, bool $withId = true): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) use ($columns, $translated, $locales, $withId) {
            // One nested group so this ANDs with the other filters, as in search().
            $query->where(function ($query) use ($columns, $translated, $locales, $withId, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$value}%");
                }

                $grammar = $query->getQuery()->getGrammar();

                foreach ($translated as $column) {
                    // Wrapped by the grammar; the JSON path is a binding.
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

    /** Exact filter matching through a relationship — e.g. users by role id. */
    protected function searchRelationById(string $name, string $relation): AllowedFilter
    {
        return AllowedFilter::callback($name, function ($query, $value) use ($relation) {
            $query->whereHas($relation, fn ($related) => $related->whereKey($value));
        });
    }

    /** search() against a related model, e.g. a pivot by its permission's code. */
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
     * Filter a polymorphic column by its public alias. An unknown alias matches
     * nothing rather than being ignored — a filter the server cannot read must
     * never widen the result set.
     */
    protected function morphType(string $name): AllowedFilter
    {
        return AllowedFilter::callback($name, function ($query, $value) use ($name) {
            $query->where($name, MorphType::classFor($value) ?? '-');
        });
    }

    /**
     * A date boundary. Two filters rather than one because spatie keys allowed
     * filters BY NAME: registering the same name twice silently keeps one. An
     * unparsable date is a 422, never a silently dropped filter.
     *
     * `$column` defaults to created_at, so a record with dates of its own (an
     * event's start_at) can use a filter name that does not match the column.
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

    /**
     * A date boundary for the AGGREGATE endpoints, which do not go through
     * spatie/query-builder and so cannot use date() above - there is no builder
     * to hang an AllowedFilter on, only a range to compute once.
     *
     * Same contract either way: an unparsable date is a 422, never a silently
     * dropped bound that would quietly widen the result.
     */
    protected function boundary(mixed $value, string $edge, string $key): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->{$edge}();
        } catch (InvalidFormatException) {
            throw ValidationException::withMessages([$key => __('validation.date', ['attribute' => 'date'])]);
        }
    }
}
