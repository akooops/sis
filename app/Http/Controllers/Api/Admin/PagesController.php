<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Page\PageData;
use App\Data\Page\StorePageData;
use App\Data\Page\UpdatePageData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Language;
use App\Models\Page;
use App\Services\Uploads\UploadService;
use App\States\Page\PageStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Content pages. Note App\Http\Controllers\Web\Admin\PagesController is a
 * different thing entirely — that one is the Inertia shell for every admin
 * screen. The name collision is a coincidence of English, not a conflict.
 */
class PagesController extends ApiController
{
    public function index(): JsonResponse
    {
        $pages = QueryBuilder::for(Page::class)
            ->with('media')
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('is_system'),
                $this->searchTranslations(
                    ['id', 'name', 'slug'],
                    ['title', 'description'],
                    Language::enabledCodes(),
                ),
            ])
            ->allowedSorts(['id', 'name', 'slug', 'status', 'published_at', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($this->perPage())
            ->appends(request()->query());

        return $this->respond(PageData::collect($pages, PaginatedDataCollection::class), 'Pages retrieved successfully');
    }

    public function show(Page $page): JsonResponse
    {
        return $this->respond(PageData::from($page), 'Page retrieved successfully');
    }

    public function store(StorePageData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $page = Page::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'status' => PageStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $page, Page::THUMBNAIL_COLLECTION);

        return $this->respond(PageData::from($page->fresh()), 'Page created successfully', 201);
    }

    public function update(UpdatePageData $data, Page $page): JsonResponse
    {
        $page->update(Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at']));

        $target = PageStatus::resolveStateClass($data->status);

        if (! $page->status instanceof $target) {
            try {
                $page->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$page->status->getValue()} page cannot become {$data->status}.",
                ]);
            }
        }

        $page->published_at = $data->status === 'published'
            ? ($page->published_at ?? now())
            : $data->published_at;
        $page->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $page, Page::THUMBNAIL_COLLECTION);
        }

        return $this->respond(PageData::from($page->fresh()), 'Page updated successfully');
    }

    public function destroy(Page $page): JsonResponse
    {
        if ($page->is_system) {
            throw ValidationException::withMessages([
                'is_system' => "The {$page->name} page ships with the app and cannot be deleted. Set it to hidden instead.",
            ]);
        }

        $page->delete();

        return $this->respond(null, 'Page deleted successfully');
    }
}
