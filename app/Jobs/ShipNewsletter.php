<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Newsletter;
use App\Models\NewsletterGroupSubscriber;
use App\Services\Integrations\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Ships one newsletter to one subscriber. SendScheduledNewsletters dispatches one
 * of these per recipient, so a slow provider or a bad address never stalls the
 * rest of the broadcast.
 *
 * Outcomes go to the `integrations` log channel, not the audit trail — the same
 * place the drivers log, and a row per recipient would flood the activity log.
 */
class ShipNewsletter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A provider hiccup is usually transient. */
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $newsletterId,
        public string $subscriberId,
        public ?string $integrationId = null,
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
        $newsletter = Newsletter::find($this->newsletterId);
        $subscriber = NewsletterGroupSubscriber::find($this->subscriberId);

        // Anything torn down since dispatch (deleted newsletter/subscriber, or an
        // unsubscribe in the meantime): nothing to ship, and no retry would help.
        if (! $newsletter || ! $subscriber || ! $subscriber->is_active || empty($subscriber->email)) {
            return;
        }

        $integration = $this->integrationId
            ? Integration::with('type')->find($this->integrationId)
            : null;

        // A pinned integration that has since gone away, been disabled or is not
        // email is a no-op — silently falling back would mail from the wrong account.
        if ($this->integrationId && (! $integration || ! $integration->is_enabled || $integration->type?->code !== 'email')) {
            return;
        }

        // Null integration = the app default mailer, which AppServiceProvider has
        // already swapped to the active email integration.
        $mailer = $integration ? Email::for($integration->id)->mailer() : Mail::mailer();

        // The admin writes the placeholders once; each recipient gets their own values.
        // One strtr over a map, so a third token is one more array line.
        $body = strtr((string) $newsletter->content, [
            Newsletter::UNSUBSCRIBE_PLACEHOLDER => $subscriber->unsubscribeUrl(),
            Newsletter::EMAIL_PLACEHOLDER => $subscriber->email,
        ]);

        $mailer->html($body, function ($message) use ($subscriber, $newsletter) {
            $message->to($subscriber->email)->subject($newsletter->subject);
        });

        $this->log('sent', $newsletter, $subscriber, $integration);
    }

    protected function log(string $outcome, Newsletter $newsletter, NewsletterGroupSubscriber $subscriber, ?Integration $integration, ?string $detail = null): void
    {
        Log::channel('integrations')->info('newsletter.'.$outcome, [
            'newsletter_id' => $newsletter->id,
            'subscriber_id' => $subscriber->id,
            'group_id' => $subscriber->newsletter_group_id,
            'integration_id' => $integration?->id,
            'channel' => $integration?->type?->code,
            'detail' => $detail,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('integrations')->error('newsletter.ship-failed', [
            'newsletter_id' => $this->newsletterId,
            'subscriber_id' => $this->subscriberId,
            'integration_id' => $this->integrationId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
