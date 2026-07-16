<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Activity\ActivityData;
use App\Enums\MorphType;
use App\Http\Controllers\Api\ApiController;
use App\Models\Activity;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * The audit trail is append-only: there is no store/update/destroy here, and
 * that is the point. Rows are written by the observers in App\Observers.
 */
class ActivitiesController extends ApiController
{
    public function index(): JsonResponse
    {
        $activities = QueryBuilder::for(Activity::query()->with(['causer', 'subject']))
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('causer_id'),
                $this->morphType('subject_type'),
                $this->morphType('causer_type'),
                $this->date('created_from', '>=', 'startOfDay'),
                $this->date('created_to', '<=', 'endOfDay'),
                $this->search(['id', 'log_name', 'event', 'description']),
            ])
            ->allowedSorts(['id', 'event', 'log_name', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(ActivityData::collect($activities, PaginatedDataCollection::class), 'Activities retrieved successfully');
    }
}
