<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Achievement\AchievementData;
use App\Data\Achievement\StoreAchievementData;
use App\Data\Achievement\UpdateAchievementData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Achievement;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Achievement\AchievementStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AchievementsController extends ApiController
{
    public function index(): JsonResponse
    {
        $achievements = QueryBuilder::for(Achievement::class)
            ->with(['media', 'category'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description', 'done_by'],
                    Language::enabledCodes(),
                ),
                $this->date('achieved_from', '>=', 'startOfDay', 'achieved_at'),
                $this->date('achieved_to', '<=', 'endOfDay', 'achieved_at'),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'published_at', 'achieved_at', 'created_at'])
            // Achievements read by when they happened, not when the row was made.
            ->defaultSort('-achieved_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(AchievementData::collect($achievements, PaginatedDataCollection::class), 'Achievements retrieved successfully');
    }

    public function show(Achievement $achievement): JsonResponse
    {
        return $this->respond(AchievementData::from($achievement), 'Achievement retrieved successfully');
    }

    public function store(StoreAchievementData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $achievement = Achievement::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'category_id' => $data->category_id,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'done_by' => [$default => $data->done_by],
            'status' => AchievementStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'achieved_at' => $data->achieved_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $achievement, Achievement::THUMBNAIL_COLLECTION);
        UploadService::sync($data->images, $achievement, Achievement::IMAGES_COLLECTION);

        return $this->respond(AchievementData::from($achievement->fresh()), 'Achievement created successfully', 201);
    }

    public function update(UpdateAchievementData $data, Achievement $achievement): JsonResponse
    {
        $achievement->update(Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at', 'images']));

        $target = AchievementStatus::resolveStateClass($data->status);

        if (! $achievement->status instanceof $target) {
            try {
                $achievement->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$achievement->status->getValue()} achievement cannot become {$data->status}.",
                ]);
            }
        }

        $achievement->published_at = $data->status === 'published'
            ? ($achievement->published_at ?? now())
            : $data->published_at;
        $achievement->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $achievement, Achievement::THUMBNAIL_COLLECTION);
        }

        UploadService::sync($data->images, $achievement, Achievement::IMAGES_COLLECTION);

        return $this->respond(AchievementData::from($achievement->fresh()), 'Achievement updated successfully');
    }

    public function destroy(Achievement $achievement): JsonResponse
    {
        $achievement->delete();

        return $this->respond(null, 'Achievement deleted successfully');
    }
}
