<?php

namespace App\Console\Commands;

use App\Models\FormSubmission;
use Illuminate\Console\Command;

/**
 * Turn stale drafts into `abandoned`.
 *
 * A draft is created by the first telemetry beacon and updated by every one
 * after it, so `updated_at` is the last time the visitor did anything. Once that
 * is old enough, they are not coming back — and until this runs, a form's funnel
 * cannot tell "still being filled in" from "given up on".
 *
 * NOT a pruning sweep, so the "Prunable + model:prune, never a bespoke command"
 * rule does not apply: this classifies rows, it does not delete them. Deleting
 * them is still model:prune's job, and `prune_started_after_hours` must stay well
 * above `abandon_after_minutes` or the prune would reach the drafts first and
 * this would have nothing to classify.
 *
 * A BULK UPDATE, deliberately. FormSubmissionObserver writes no activity row for
 * a submission update (a submission is written by the public, several times per
 * visit), so there is nothing for model events to tell anybody — and this can
 * touch thousands of rows a night. It is also why `status` is a plain string
 * rather than a state machine: a state machine forbids exactly this.
 *
 * The per-field `is_abandoned` flag is NOT set here. The beacon already carries
 * which field was open and TelemetryRecorder writes it on every snapshot, so the
 * last one the visitor sent is already correct.
 */
class CloseAbandonedFormSubmissions extends Command
{
    protected $signature = 'forms:close-abandoned';

    protected $description = 'Mark idle form drafts as abandoned';

    /** Rows per statement, so one very bad night cannot lock the table. */
    protected const CHUNK = 1000;

    public function handle(): int
    {
        $minutes = max(1, (int) config('forms.submissions.abandon_after_minutes', 30));
        $cutoff = now()->subMinutes($minutes);
        $closed = 0;

        do {
            // Re-queried each pass rather than chunked with an offset: the update
            // removes rows from the result set, which is precisely what makes an
            // offset walk skip half of them.
            $ids = FormSubmission::query()
                ->where('status', 'started')
                ->where('updated_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(self::CHUNK)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            $closed += FormSubmission::query()
                ->whereIn('id', $ids)
                ->where('status', 'started')
                ->update(['status' => 'abandoned', 'updated_at' => now()]);
        } while (count($ids) === self::CHUNK);

        $this->info("Closed {$closed} abandoned submission(s).");

        return self::SUCCESS;
    }
}
