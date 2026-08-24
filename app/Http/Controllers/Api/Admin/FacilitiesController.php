<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Facility\FacilityData;
use App\Data\Facility\StoreFacilityData;
use App\Data\Facility\UpdateFacilityData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Facility;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Facility\FacilityStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class FacilitiesController extends ApiController
{
    public function index(): JsonResponse
    {
        $facilities = QueryBuilder::for(Facility::class)
            ->with(['media'])
            ->withCount([
                'slots',
                'slots as open_slots_count' => fn ($query) => $query->open(),
                'reservations',
                // The pivot rows, not the related records: counting the links is
                // one join instead of two, and the number is the same.
                'articleLinks',
                'albumLinks',
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
            ->allowedSorts(['id', 'name', 'slug', 'status', 'order', 'published_at', 'created_at'])
            // By `order`, not by date: these are a hand-arranged list on a public
            // page, and the admin table should read in the order a visitor sees.
            ->defaultSort('order')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(FacilityData::collect($facilities, PaginatedDataCollection::class), 'Facilities retrieved successfully');
    }

    public function show(Facility $facility): JsonResponse
    {
        return $this->respond(FacilityData::from($facility), 'Facility retrieved successfully');
    }

    public function store(StoreFacilityData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $facility = Facility::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'order' => $data->order,
            'status' => FacilityStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
        ]);

        UploadService::attach($data->thumbnail, $facility, Facility::THUMBNAIL_COLLECTION);

        return $this->respond(FacilityData::from($facility->fresh()), 'Facility created successfully', 201);
    }

    public function update(UpdateFacilityData $data, Facility $facility): JsonResponse
    {
        $attributes = Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']);

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $facility->update($facility->mergeTranslations($attributes));

        $target = FacilityStatus::resolveStateClass($data->status);

        if (! $facility->status instanceof $target) {
            try {
                $facility->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$facility->status->getValue()} facility cannot become {$data->status}.",
                ]);
            }
        }

        $facility->published_at = $data->status === 'published'
            ? ($facility->published_at ?? now())
            : $data->published_at;
        $facility->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $facility, Facility::THUMBNAIL_COLLECTION);
        }

        return $this->respond(FacilityData::from($facility->fresh()), 'Facility updated successfully');
    }

    /**
     * REFUSED WHILE ANYONE IS BOOKED ON IT.
     *
     * Both FKs cascade, so deleting a venue would take its time slots and every
     * booking against them with it — people expecting to be let in at the gate,
     * gone with no record that they ever booked. Hiding the venue is the
     * reversible way to take it off the site, and the message says so.
     *
     * The attached articles and albums are NOT a reason to refuse: those rows are
     * links, and losing them loses nothing but the association — the articles and
     * albums themselves are untouched.
     */
    public function destroy(Facility $facility): JsonResponse
    {
        $booked = $facility->reservations()->active()->count();

        if ($booked > 0) {
            throw ValidationException::withMessages([
                'id' => "This facility has {$booked} active booking(s) and cannot be deleted. Hide it instead, or cancel the bookings first.",
            ]);
        }

        $facility->delete();

        return $this->respond(null, 'Facility deleted successfully');
    }
}
