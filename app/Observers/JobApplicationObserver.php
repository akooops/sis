<?php

namespace App\Observers;

use App\Models\JobApplication;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The audit trail HR actually needs: who moved this application, when, and to
 * what. Every status change lands here as an ordinary updated() diff.
 *
 * It also emits the two job notifications. OBSERVER-EMITTED, like every other
 * notification in this app — a controller that sent its own would be a second
 * place the rules live, and a status changed by a queue or a console command
 * would announce nothing.
 */
class JobApplicationObserver extends BaseObserver
{
    /**
     * A new application arrived.
     *
     * Announced on CREATE rather than from the projector, so a re-projection of
     * an existing application — which is idempotent and routine — never tells HR
     * about it a second time.
     */
    public function created(Model $model): void
    {
        parent::created($model);

        /** @var JobApplication $application */
        $application = $model;

        $this->announce(
            $application,
            'job.application_received',
            'New application: '.$this->who($application),
            $this->who($application).' applied for '.$this->posting($application).'.',
        );
    }

    /**
     * A status moved.
     *
     * Only the status: an application is also written when the projector re-runs
     * over it, and a notification for "the same person applied again and we
     * re-read their CV" is noise nobody can act on.
     */
    public function updated(Model $model): void
    {
        parent::updated($model);

        /** @var JobApplication $application */
        $application = $model;

        if (! $application->wasChanged('status')) {
            return;
        }

        $from = $application->getOriginal('status');

        $this->announce(
            $application,
            'job.application_status_changed',
            $this->who($application).' is now '.$application->status,
            $this->who($application).' moved from '.$from.' to '.$application->status
                .' for '.$this->posting($application).'.',
        );
    }

    /**
     * Send, and never let a failure cost the write.
     *
     * The status change is the record; an integration being down must not roll it
     * back or throw out of an observer into whatever triggered it.
     */
    protected function announce(JobApplication $application, string $type, string $title, string $body): void
    {
        try {
            NotificationService::send($type, [
                'title' => $title,
                'body' => $body,
                'route_name' => 'web.admin.job-applications.index',
                'route_params' => ['filter[id]' => $application->id],
            ]);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('jobs.notify-failed', [
                'application' => $application->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function who(JobApplication $application): string
    {
        return $application->candidate?->full_name ?: 'An applicant';
    }

    protected function posting(JobApplication $application): string
    {
        return $application->jobOffer?->name ?: 'a posting';
    }

    protected function logName(): string
    {
        return 'job-applications';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['candidate_id', 'job_offer_id', 'status'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return [
            'name' => $model->candidate?->full_name ?? $model->candidate_id,
            'job_offer' => $model->jobOffer?->name,
            'status' => (string) $model->status,
        ];
    }
}
