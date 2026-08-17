<?php

namespace App\Observers;

use App\Jobs\Ai\EmbedJobOffer;
use App\Models\JobOffer;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JobOfferObserver extends BaseObserver
{
    /** Free the media back to the pool rather than destroying it: it outlives its owner. */
    public function deleting(JobOffer $jobOffer): void
    {
        UploadService::freeModel($jobOffer);
    }

    public function created(Model $model): void
    {
        parent::created($model);

        $this->embed($model);
    }

    /**
     * Re-embed when what the posting SAYS changes.
     *
     * Gated on the descriptive columns, not on any write: a posting is also saved
     * when its status flips or the scheduler publishes it, and re-embedding then
     * would spend a provider call to produce the identical vector.
     *
     * Also embedded on a status change, but only because a draft that was never
     * embedded becomes visible to matching the moment it publishes.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        if ($model->wasChanged(['name', 'title', 'description', 'content', 'skills', 'education_level', 'experience_years', 'employment_type', 'work_mode'])
            || ($model->wasChanged('status') && $model->embedding === null)) {
            $this->embed($model);
        }
    }

    /**
     * Queue the vector that puts this posting in a talent pool.
     *
     * AFTER COMMIT, because the job re-reads the row by id and on a synchronous
     * queue would otherwise find the pre-write version — or nothing at all, on a
     * create.
     */
    protected function embed(Model $model): void
    {
        DB::afterCommit(fn () => EmbedJobOffer::dispatch($model->getKey()));
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
