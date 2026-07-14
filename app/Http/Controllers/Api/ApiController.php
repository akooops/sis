<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\QueryBuilder\AllowedFilter;

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
     * An exact filter (?filter[<name>]=<id>) that matches records related to a
     * given id through a relationship — e.g. users by role id, roles by
     * permission id, or (in domain modules) students by their guardian id.
     */
    protected function relatedId(string $name, string $relation): AllowedFilter
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
    protected function searchRelation(string $relation, array $columns): AllowedFilter
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
}
