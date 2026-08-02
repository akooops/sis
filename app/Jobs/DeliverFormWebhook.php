<?php

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Models\FormWebhook;
use App\Services\Forms\WebhookPayload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * One outbound delivery.
 *
 * The timeout is not optional. QUEUE_CONNECTION is `sync`, so until a real queue
 * lands this runs inline on the visitor's request — a dead endpoint would hold
 * their browser open for the full connection timeout while they stare at a
 * spinner on a form they have already filled in.
 *
 * The webhook row only records WHEN it last fired; the status code, the timing
 * and the error go to the `integrations` log channel, the same as every other
 * outbound driver in this app.
 */
class DeliverFormWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public string $webhookId,
        public string $submissionId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        $webhook = FormWebhook::find($this->webhookId);
        $submission = FormSubmission::with('form')->find($this->submissionId);

        // Either could have been deleted between dispatch and run.
        if (! $webhook || ! $submission || ! $webhook->is_enabled) {
            return;
        }

        $body = json_encode(WebhookPayload::build($submission));
        $headers = WebhookPayload::headers($webhook, $body);

        $started = microtime(true);

        $response = Http::withHeaders($headers)
            ->timeout((int) config('forms.webhooks.timeout', 10))
            ->withBody($body, 'application/json')
            ->send($webhook->method ?: 'POST', $webhook->url);

        $duration = (int) round((microtime(true) - $started) * 1000);

        $webhook->recordDelivery();

        Log::channel('integrations')->info('form-webhook.delivered', [
            'webhook' => $webhook->id,
            'submission' => $submission->id,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'ms' => $duration,
            // A snippet only, and never the request body — that is the
            // visitor's data and it is already stored once.
            'response' => Str::limit($response->body(), 500),
        ]);

        // A 4xx/5xx has to throw, or the retry never happens and `failed()`
        // never runs — the delivery would silently look like a success.
        $response->throw();
    }

    /**
     * The retries are spent. The row only records that something went out and
     * when; WHY it failed is this log line, which is the one place with the
     * exception in it.
     */
    public function failed(?Throwable $exception): void
    {
        $webhook = FormWebhook::find($this->webhookId);

        $webhook?->recordDelivery();

        Log::channel('integrations')->error('form-webhook.failed', [
            'webhook' => $this->webhookId,
            'submission' => $this->submissionId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
