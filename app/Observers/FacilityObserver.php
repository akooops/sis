<?php

namespace App\Observers;

use App\Models\Facility;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class FacilityObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(Facility $facility): void
    {
        UploadService::freeModel($facility);
    }

    protected function logName(): string
    {
        return 'facilities';
    }

    /**
     * All three are every locale at once, and updated() writes both sides of a
     * diff — logging them would put two copies of the venue's body in every audit
     * row. custom_css is the same problem at a smaller scale.
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
