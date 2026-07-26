<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Event\EventData;
use App\Data\Event\StoreEventData;
use App\Data\Event\UpdateEventData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Event;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Event\EventStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class EventsController extends ApiController
{
    public function index(): JsonResponse
    {
        $events = QueryBuilder::for(Event::class)
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
                // Two distinct names bounding the same column — a Filters
                // `daterange` keyed `start` submits exactly these.
                $this->date('start_from', '>=', 'startOfDay', 'start_at'),
                $this->date('start_to', '<=', 'endOfDay', 'start_at'),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'published_at', 'start_at', 'end_at', 'created_at'])
            ->defaultSort('-start_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(EventData::collect($events, PaginatedDataCollection::class), 'Events retrieved successfully');
    }

    public function show(Event $event): JsonResponse
    {
        return $this->respond(EventData::from($event), 'Event retrieved successfully');
    }

    public function store(StoreEventData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $event = Event::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'status' => EventStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'start_at' => $data->start_at,
            'end_at' => $data->end_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $event, Event::THUMBNAIL_COLLECTION);
        UploadService::sync($data->images, $event, Event::IMAGES_COLLECTION);

        return $this->respond(EventData::from($event->fresh()), 'Event created successfully', 201);
    }

    public function update(UpdateEventData $data, Event $event): JsonResponse
    {
        $event->update(Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at', 'images']));

        $target = EventStatus::resolveStateClass($data->status);

        if (! $event->status instanceof $target) {
            try {
                $event->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$event->status->getValue()} event cannot become {$data->status}.",
                ]);
            }
        }

        $event->published_at = $data->status === 'published'
            ? ($event->published_at ?? now())
            : $data->published_at;
        $event->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $event, Event::THUMBNAIL_COLLECTION);
        }

        UploadService::sync($data->images, $event, Event::IMAGES_COLLECTION);

        return $this->respond(EventData::from($event->fresh()), 'Event updated successfully');
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return $this->respond(null, 'Event deleted successfully');
    }
}
