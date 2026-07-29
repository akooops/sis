<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Calendar\CalendarData;
use App\Data\Calendar\StoreCalendarData;
use App\Data\Calendar\UpdateCalendarData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Calendar;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CalendarsController extends ApiController
{
    public function index(): JsonResponse
    {
        $calendars = QueryBuilder::for(Calendar::class)
            // The DTO reads the file off the media relation on every row.
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('is_active'),
                $this->date('starts_from', '>=', 'startOfDay', 'start_date'),
                $this->date('starts_to', '<=', 'endOfDay', 'start_date'),
                $this->searchTranslations(['id', 'name'], ['title'], Language::enabledCodes()),
            ])
            ->allowedSorts(['id', 'name', 'start_date', 'end_date', 'created_at'])
            // Newest period first — an editor looks for the current year.
            ->defaultSort('-start_date')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(CalendarData::collect($calendars, PaginatedDataCollection::class), 'Calendars retrieved successfully');
    }

    public function show(Calendar $calendar): JsonResponse
    {
        return $this->respond(CalendarData::from($calendar->load('media')), 'Calendar retrieved successfully');
    }

    public function store(StoreCalendarData $data): JsonResponse
    {
        $calendar = Calendar::create([
            'name' => $data->name,
            'title' => [Language::defaultCode() => $data->title],
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'is_active' => $data->is_active,
        ]);

        UploadService::attach($data->file, $calendar, Calendar::FILE_COLLECTION);

        return $this->respond(CalendarData::from($calendar->fresh()->load('media')), 'Calendar created successfully', 201);
    }

    public function update(UpdateCalendarData $data, Calendar $calendar): JsonResponse
    {
        $calendar->update($calendar->mergeTranslations([
            'name' => $data->name,
            'title' => $data->title,
            'start_date' => $data->start_date,
            'end_date' => $data->end_date,
            'is_active' => $data->is_active,
        ]));

        if (! $data->file instanceof Optional && $data->file) {
            UploadService::attach($data->file, $calendar, Calendar::FILE_COLLECTION);
        }

        return $this->respond(CalendarData::from($calendar->fresh()->load('media')), 'Calendar updated successfully');
    }

    public function destroy(Calendar $calendar): JsonResponse
    {
        $calendar->delete();

        return $this->respond(null, 'Calendar deleted successfully');
    }
}
