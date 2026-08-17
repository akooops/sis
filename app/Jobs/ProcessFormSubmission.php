<?php

namespace App\Jobs;

use App\Contracts\Forms\SubmissionProjector;
use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Everything that happens AFTER a submission is safely stored: notify the
 * routed groups, then fire the webhooks.
 *
 * Dispatched with dispatchAfterResponse(), and that is the whole point.
 * QUEUE_CONNECTION is `sync`, so without it every recipient's SMTP round-trip
 * and every webhook's HTTP call would run while the visitor's browser waits on
 * a form they have already submitted — a ten-member group with an email
 * integration would be ten sequential sends before the thank-you page renders.
 * After-response runs the same work once the response has been flushed.
 *
 * When a real queue lands this becomes an ordinary background job and the
 * behaviour improves again with no code change.
 *
 * Nothing here may surface to the visitor. Their submission is stored and their
 * confirmation is owed regardless of whether a webhook endpoint is up.
 */
class ProcessFormSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** How many answers the notification lists before it says "and N more". */
    protected const SUMMARY_FIELDS = 6;

    /** Per-answer cap, so one long textarea cannot become the whole email. */
    protected const SUMMARY_VALUE_CHARS = 80;

    public function __construct(public string $submissionId) {}

    public function handle(): void
    {
        $submission = FormSubmission::with('form')->find($this->submissionId);
        $form = $submission?->form;

        if (! $submission || ! $form) {
            return;
        }

        $this->project($form, $submission);
        $this->notify($form, $submission);
        $this->deliver($form, $submission);
    }

    /**
     * Hand a completed submission to whatever domain owns this form.
     *
     * THE SEAM, and it is a LOOKUP rather than a branch: config('forms.projectors')
     * maps a form slug to a SubmissionProjector, so a second domain — a visit
     * reservation, say — is a class and a config line, with nothing in the forms
     * module changing to accommodate it. A form with no projector is the normal
     * case and does nothing here.
     *
     * HERE RATHER THAN IN AN OBSERVER, for the same reason the webhook delivery is:
     * FormSubmissionObserver::created() fires inside the submit transaction, and
     * projecting there would hold a write lock across the whole domain write while
     * the visitor waits. This job already runs after the response has been sent.
     *
     * FIRST, before notify() and deliver(), so a notification about a new
     * application or booking is sent only once the row exists to link to.
     *
     * WRAPPED, because a projection failure must not cost the submission. The
     * answers are already stored and a projector is required to be idempotent, so
     * a failed run is repairable by running it again — losing the notification and
     * the webhook over it would not be.
     */
    protected function project(Form $form, FormSubmission $submission): void
    {
        $class = config('forms.projectors', [])[$form->slug] ?? null;

        if ($class === null) {
            return;
        }

        try {
            $projector = app($class);

            // A misconfigured entry must not take the rest of the job down with
            // it — the notification and the webhook still have to go out.
            if ($projector instanceof SubmissionProjector) {
                $projector->project($submission);
            }
        } catch (Throwable $e) {
            Log::channel('integrations')->error('form.projection-failed', [
                'submission' => $submission->id,
                'form' => $form->slug,
                'projector' => $class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The groups attached to THIS FORM, plus anyone subscribed to the type.
     *
     * form.submission_received is an ordinary subscribable type — a group that
     * ticks it hears about every form — and the groups attached to the form are
     * handed in on top of that, so a form also notifies its own audience
     * without anyone subscribing globally. The two lists are a union, and a
     * user in both gets one notification.
     */
    protected function notify(Form $form, FormSubmission $submission): void
    {
        try {
            $groupIds = $form->notificationGroups()->pluck('notification_groups.id')->all();

            NotificationService::send('form.submission_received', [
                'title' => "New response to {$form->name}",
                'body' => $this->body($form, $submission),
                'route_name' => 'web.admin.forms.index',
                'route_params' => [
                    'filter[id]' => $form->id,
                ],
            ], $groupIds);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('form.notify-failed', [
                'submission' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * What the notification actually says.
     *
     * It used to be "Reference 01KZ….", which told the reader nothing they could
     * act on: not what was submitted, not when, not by whom — so every
     * notification was a trip to the admin to find out whether it mattered. This
     * carries the reference, the timestamp and the first few answers, which is
     * usually enough to triage without opening anything.
     *
     * DELIBERATELY A SUMMARY, NOT THE SUBMISSION. It is capped at
     * self::SUMMARY_FIELDS answers with each value trimmed, because this string
     * is also what an email integration sends: the whole of a long form, or an
     * essay-length textarea, does not belong in an inbox, and the drawer is one
     * click away. Blank answers are skipped — a list of empty labels is noise.
     */
    protected function body(Form $form, FormSubmission $submission): string
    {
        $when = ($submission->submitted_at ?? $submission->created_at)?->format('j M Y, H:i');

        $lines = ["Reference {$submission->id}".($when ? " — submitted {$when}." : '.')];

        $answers = $this->answers($form, $submission);

        if ($answers !== []) {
            $shown = array_slice($answers, 0, self::SUMMARY_FIELDS);
            $rest = count($answers) - count($shown);

            $lines[] = '';
            $lines = array_merge($lines, $shown);

            if ($rest > 0) {
                $lines[] = '…and '.$rest.' more '.($rest === 1 ? 'answer' : 'answers').'.';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * The answered fields as "Label: value", in the form's reading order.
     *
     * Rendered through the field type's own display(), the same call the CSV
     * export makes, so a consent reads Yes/No and a checkbox group reads as a
     * list rather than as JSON. File answers resolve to their filenames first —
     * a line of ULIDs tells the reader nothing.
     *
     * @return array<int, string>
     */
    protected function answers(Form $form, FormSubmission $submission): array
    {
        $data = $submission->data ?? [];

        if ($data === []) {
            return [];
        }

        $locale = config('app.fallback_locale', 'en');
        $names = $submission->relationLoaded('media')
            ? $submission->media->pluck('name', 'id')
            : $submission->media()->pluck('name', 'id');

        $lines = [];

        // The form's CURRENT fields, ordered by the query rather than by the
        // stored snapshot: MySQL's JSON type does not keep object key order.
        //
        // Top-level only, matching the answer map — a child has no entry of its
        // own in `data`, and `children` is loaded so a group can flatten itself
        // for the summary line without a query per group.
        $fields = $form->fields()
            ->topLevel()
            ->with(['page', 'children'])
            ->get()
            ->sortBy([['page.order', 'asc'], ['order', 'asc'], ['key', 'asc']]);

        foreach ($fields as $field) {
            $element = $field->element();

            if (! $element?->isInput() || ! array_key_exists($field->key, $data)) {
                continue;
            }

            $value = $data[$field->key];

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            if ($field->type === 'file') {
                $resolve = fn ($id) => is_string($id) ? ($names[$id] ?? $id) : $id;
                $value = is_array($value) ? array_map($resolve, $value) : $resolve($value);
            }

            $text = trim((string) $element->display($field, $value, $locale));

            if ($text === '') {
                continue;
            }

            // The KEY, not the label. This notification is an admin surface, and
            // the admin names a field the same way everywhere — the builder card,
            // the CSV column, the submission drawer. The label is the visitor's
            // translated title and would put Arabic headings in an English inbox.
            $lines[] = $field->key.': '.Str::limit($text, self::SUMMARY_VALUE_CHARS);
        }

        return $lines;
    }

    protected function deliver(Form $form, FormSubmission $submission): void
    {
        foreach ($form->webhooks()->where('is_enabled', true)->get() as $webhook) {
            try {
                DeliverFormWebhook::dispatch($webhook->id, $submission->id);
            } catch (Throwable $e) {
                Log::channel('integrations')->error('form.webhook-dispatch-failed', [
                    'webhook' => $webhook->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('integrations')->error('form.side-effects-failed', [
            'submission' => $this->submissionId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
