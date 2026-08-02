<?php

namespace App\Observers;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Uploads\UploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * The one observer that deliberately does NOT audit most of its lifecycle.
 *
 * A submission is written by the public, several times per visit — a draft on
 * the first beacon, an update on every beacon after it. An activity row per
 * write would be one row per page view, which is the same write amplification
 * that keeps sessions out of the audit log entirely. The submission table IS
 * the record of what happened; it does not need a second one.
 *
 * Deletes are still audited, because those are an admin destroying data — but
 * only when there is a causer. The nightly model:prune has none, and a
 * retention sweep would otherwise write thousands of causer-null rows a night.
 */
class FormSubmissionObserver extends BaseObserver
{
    /**
     * Free the answer files back to the pool (they outlive the submission row),
     * and give the form's counter back what this row took.
     *
     * deleting(), not deleted(): the status and form_id are needed, and they are
     * the row's own columns. The decrement is a builder update so it fires no
     * model events — FormObserver stays quiet, and `submissions_count` is in its
     * ignored() list anyway. Only `completed` counts, because that is the only
     * status the submit pipeline increments on; `> 0` guards the unsigned
     * column. model:prune cannot double-decrement: prunable() excludes completed
     * rows.
     */
    public function deleting(FormSubmission $submission): void
    {
        UploadService::freeModel($submission);

        if ($submission->status === 'completed' && $submission->form_id) {
            Form::whereKey($submission->form_id)
                ->where('submissions_count', '>', 0)
                ->decrement('submissions_count');
        }
    }

    public function created(Model $model): void
    {
        // No audit row. Notifications are emitted from the submit pipeline
        // after the transaction commits, not here: created() fires INSIDE it,
        // and on a sync queue that would ship an SMTP round-trip per recipient
        // before the row is even committed.
    }

    public function updated(Model $model): void
    {
        // No audit row — see the class docblock.
    }

    public function deleted(Model $model): void
    {
        if (! Auth::hasUser() && ! request()?->attributes->get('apiKey')) {
            return;
        }

        parent::deleted($model);
    }

    protected function logName(): string
    {
        return 'form-submissions';
    }

    /**
     * @return array<int, string>
     */
    protected function loggedOnDelete(): array
    {
        return ['form_id', 'status', 'submitted_at', 'country_code'];
    }

    /**
     * The ULID, because a submission has no name and no reference — the id is
     * what the visitor was shown and what the admin searches on.
     *
     * @return array<string, mixed>
     */
    protected function meta(Model $model): array
    {
        return ['name' => $model->id];
    }
}
