<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\JobOffer\JobOfferData;
use App\Data\JobOffer\StoreJobOfferData;
use App\Data\JobOffer\UpdateJobOfferData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use App\Models\JobOffer;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\JobOffer\JobOfferStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class JobOffersController extends ApiController
{
    public function index(): JsonResponse
    {
        $jobOffers = QueryBuilder::for(JobOffer::class)
            ->with(['media', 'category'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::exact('employment_type'),
                AllowedFilter::exact('work_mode'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'published_at', 'deadline_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(JobOfferData::collect($jobOffers, PaginatedDataCollection::class), 'Job offers retrieved successfully');
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->respond(JobOfferData::from($jobOffer->load('category')), 'Job offer retrieved successfully');
    }

    public function store(StoreJobOfferData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $jobOffer = JobOffer::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'category_id' => $data->category_id ?? Category::default()?->id,
            'employment_type' => $data->employment_type,
            'work_mode' => $data->work_mode,
            'experience_years' => $data->experience_years,
            'education_level' => $data->education_level,
            'start_date' => $data->start_date,
            'deadline_at' => $data->deadline_at,
            'status' => JobOfferStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'address' => [$default => $data->address],
            'skills' => [$default => JobOffer::joinSkills($data->skills)],
        ]);

        UploadService::attach($data->thumbnail, $jobOffer, JobOffer::THUMBNAIL_COLLECTION);

        return $this->respond(JobOfferData::from($jobOffer->fresh()->load('category')), 'Job offer created successfully', 201);
    }

    public function update(UpdateJobOfferData $data, JobOffer $jobOffer): JsonResponse
    {
        $attributes = Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']);
        $attributes['category_id'] ??= Category::default()?->id;

        // The column holds one joined string per locale; the API speaks arrays.
        $attributes['skills'] = array_map([JobOffer::class, 'joinSkills'], $attributes['skills']);

        // mergeTranslations: the form only carries the enabled locales, so a plain
        // assignment would drop every disabled one.
        $jobOffer->update($jobOffer->mergeTranslations($attributes));

        $target = JobOfferStatus::resolveStateClass($data->status);

        if (! $jobOffer->status instanceof $target) {
            try {
                $jobOffer->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$jobOffer->status->getValue()} job offer cannot become {$data->status}.",
                ]);
            }
        }

        $jobOffer->published_at = $data->status === 'published'
            ? ($jobOffer->published_at ?? now())
            : $data->published_at;
        $jobOffer->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $jobOffer, JobOffer::THUMBNAIL_COLLECTION);
        }

        return $this->respond(JobOfferData::from($jobOffer->fresh()->load('category')), 'Job offer updated successfully');
    }

    public function destroy(JobOffer $jobOffer): JsonResponse
    {
        $jobOffer->delete();

        return $this->respond(null, 'Job offer deleted successfully');
    }
}
