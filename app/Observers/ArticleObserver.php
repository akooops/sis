<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class ArticleObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Article $article): void
    {
        UploadService::freeModel($article);
    }

    protected function logName(): string
    {
        return 'articles';
    }

    /**
     * `content` is every locale's full HTML, and updated() writes both sides of a
     * diff — logging it would put two copies of the body in every audit row.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['content'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'slug', 'status', 'published_at'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name];
    }
}
