<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\VisitService\StoreVisitServiceData;
use App\Data\VisitService\UpdateVisitServiceData;
use App\Data\VisitService\VisitServiceData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\VisitService;
use App\Services\Uploads\UploadService;
use App\States\VisitService\VisitServiceStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class VisitServicesController extends ApiController
{
    public function index(): JsonResponse
    {
        $services = QueryBuilder::for(VisitService::class)
            ->with(['media'])
            ->withCount([
                'slots',
                'slots as open_slots_count' => fn ($query) => $query->open(),
                'reservations',
            ])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'order', 'duration_minutes', 'published_at', 'created_at'])
            // By `order`, not by date: these are a hand-arranged list on a public
            // page, and the admin table should read in the order a visitor sees.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(VisitServiceData::collect($services, PaginatedDataCollection::class), 'Visit services retrieved successfully');
    }

    public function show(VisitService $visitService): JsonResponse
    {
        return $this->respond(VisitServiceData::from($visitService), 'Visit service retrieved successfully');
    }

    public function store(StoreVisitServiceData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $service = VisitService::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'duration_minutes' => $data->duration_minutes,
            'max_visitors' => $data->max_visitors,
            'order' => $data->order,
            'status' => VisitServiceStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
        ]);

        UploadService::attach($data->thumbnail, $service, VisitService::THUMBNAIL_COLLECTION);

        return $this->respond(VisitServiceData::from($service->fresh()), 'Visit service created successfully', 201);
    }

    public function update(UpdateVisitServiceData $data, VisitService $visitService): JsonResponse
    {
        $attributes = Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']);

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $visitService->update($visitService->mergeTranslations($attributes));

        $target = VisitServiceStatus::resolveStateClass($data->status);

        if (! $visitService->status instanceof $target) {
            try {
                $visitService->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$visitService->status->getValue()} visit service cannot become {$data->status}.",
                ]);
            }
        }

        $visitService->published_at = $data->status === 'published'
            ? ($visitService->published_at ?? now())
            : $data->published_at;
        $visitService->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $visitService, VisitService::THUMBNAIL_COLLECTION);
        }

        return $this->respond(VisitServiceData::from($visitService->fresh()), 'Visit service updated successfully');
    }

    /**
     * REFUSED WHILE ANYONE IS BOOKED ON IT.
     *
     * Both FKs cascade, so deleting a service would take its slots and every
     * reservation against them with it — families who are expecting to be let in
     * at the gate, gone with no record that they ever booked. Hiding the service
     * is the reversible way to take it off the site, and the message says so.
     */
    public function destroy(VisitService $visitService): JsonResponse
    {
        $booked = $visitService->reservations()->active()->count();

        if ($booked > 0) {
            throw ValidationException::withMessages([
                'id' => "This visit has {$booked} active reservation(s) and cannot be deleted. Hide it instead, or cancel the reservations first.",
            ]);
        }

        $visitService->delete();

        return $this->respond(null, 'Visit service deleted successfully');
    }
}
