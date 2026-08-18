<?php

namespace App\Observers;

use App\Models\VisitService;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class VisitServiceObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(VisitService $visitService): void
    {
        UploadService::freeModel($visitService);
    }

    protected function logName(): string
    {
        return 'visit-services';
    }

    /**
     * All three are every locale at once, and updated() writes both sides of a
     * diff — logging them would put two copies of the popup body in every audit
     * row. custom_css is the same shape of problem at a smaller scale.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['title', 'description', 'content', 'custom_css'];
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['name', 'slug', 'status'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->name, 'status' => (string) $model->status];
    }
}
