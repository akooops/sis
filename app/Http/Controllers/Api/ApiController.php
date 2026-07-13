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
     * columns with a LIKE, independent of the per-field filters.
     *
     * @param  array<int, string>  $columns
     */
    protected function search(array $columns): AllowedFilter
    {
        return AllowedFilter::callback('search', function ($query, $value) use ($columns) {
            $query->where(function ($query) use ($columns, $value) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', "%{$value}%");
                }
            });
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
