<?php

namespace App\Http\Controllers\Api\Admin;

use App\Data\Article\ArticleData;
use App\Data\Article\StoreArticleData;
use App\Data\Article\UpdateArticleData;
use App\Http\Controllers\Api\ApiController;
use App\Models\Article;
use App\Models\Language;
use App\Services\Uploads\UploadService;
use App\States\Article\ArticleStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\ModelStates\Exceptions\TransitionNotFound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ArticlesController extends ApiController
{
    public function index(): JsonResponse
    {
        $articles = QueryBuilder::for(Article::class)
            ->with(['media', 'category'])
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
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

        return $this->respond(ArticleData::collect($articles, PaginatedDataCollection::class), 'Articles retrieved successfully');
    }

    public function show(Article $article): JsonResponse
    {
        return $this->respond(ArticleData::from($article), 'Article retrieved successfully');
    }

    public function store(StoreArticleData $data): JsonResponse
    {
        $default = Language::defaultCode();

        $article = Article::create([
            'name' => $data->name,
            'slug' => $data->slug,
            'category_id' => $data->category_id,
            'title' => [$default => $data->title],
            'description' => [$default => $data->description],
            'content' => [$default => $data->content],
            'status' => ArticleStatus::resolveStateClass($data->status),
            'published_at' => $data->status === 'published' ? now() : $data->published_at,
            'css_url' => $data->css_url,
            'custom_css' => $data->custom_css,
        ]);

        UploadService::attach($data->thumbnail, $article, Article::THUMBNAIL_COLLECTION);

        // Link the images the content uses so the orphan sweep can't reclaim them.
        UploadService::sync($data->images, $article, Article::IMAGES_COLLECTION);

        return $this->respond(ArticleData::from($article->fresh()), 'Article created successfully', 201);
    }

    public function update(UpdateArticleData $data, Article $article): JsonResponse
    {
        $article->update(Arr::except($data->toArray(), ['status', 'thumbnail', 'published_at', 'images']));

        $target = ArticleStatus::resolveStateClass($data->status);

        if (! $article->status instanceof $target) {
            try {
                $article->status->transitionTo($target);
            } catch (TransitionNotFound) {
                throw ValidationException::withMessages([
                    'status' => "A {$article->status->getValue()} article cannot become {$data->status}.",
                ]);
            }
        }

        $article->published_at = $data->status === 'published'
            ? ($article->published_at ?? now())
            : $data->published_at;
        $article->save();

        if (! $data->thumbnail instanceof Optional && $data->thumbnail) {
            UploadService::attach($data->thumbnail, $article, Article::THUMBNAIL_COLLECTION);
        }

        UploadService::sync($data->images, $article, Article::IMAGES_COLLECTION);

        return $this->respond(ArticleData::from($article->fresh()), 'Article updated successfully');
    }

    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return $this->respond(null, 'Article deleted successfully');
    }
}
