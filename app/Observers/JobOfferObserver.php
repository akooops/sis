<?php

namespace App\Observers;

use App\Models\JobOffer;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;

class JobOfferObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(JobOffer $jobOffer): void
    {
        UploadService::freeModel($jobOffer);
    }

    protected function logName(): string
    {
        return 'job-offers';
    }

    /**
     * All three are every locale at once, and updated() writes both sides of a diff —
     * logging them would put two copies of the body in every audit row.
     *
     * @return array<int, string>
     */
    protected function ignored(): array
    {
        return ['content', 'skills', 'address'];
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
        return ['name' => $model->name];
    }
}
