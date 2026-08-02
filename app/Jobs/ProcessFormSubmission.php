<?php

namespace App\Jobs;

use App\Models\Form;
use App\Models\FormSubmission;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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

    public function __construct(public string $submissionId) {}

    public function handle(): void
    {
        $submission = FormSubmission::with('form')->find($this->submissionId);
        $form = $submission?->form;

        if (! $submission || ! $form) {
            return;
        }

        $this->notify($form, $submission);
        $this->deliver($form, $submission);
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
                'body' => "Reference {$submission->id}.",
                'route_name' => 'web.admin.forms.index',
                'route_params' => ['form' => $form->id],
            ], $groupIds);
        } catch (Throwable $e) {
            Log::channel('integrations')->error('form.notify-failed', [
                'submission' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
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
