<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Notification;
use App\Models\User;
use App\Services\Integrations\Email;
use App\Services\Integrations\Sms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ships one notification to one user through one integration (email or sms).
 * Dispatched by NotificationService after the in-app row is already written, so a
 * provider being slow or down never blocks the notification itself.
 *
 * Outcomes go to the `integrations` log channel, not the audit trail — the same
 * place the drivers log, and per-recipient sends would flood the activity log.
 */
class ShipNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** A provider hiccup is usually transient. */
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $notificationId,
        public string $userId,
        public string $integrationId,
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
        $notification = Notification::find($this->notificationId);
        $user = User::find($this->userId);
        $integration = Integration::with('type')->find($this->integrationId);

        // Anything torn down since dispatch (deleted integration/user/notification,
        // or the integration disabled): nothing to ship, and no retry would help.
        if (! $notification || ! $user || ! $integration || ! $integration->is_enabled) {
            return;
        }

        $channel = $integration->type?->code;

        match ($channel) {
            'email' => $this->shipEmail($notification, $user, $integration),
            'sms' => $this->shipSms($notification, $user, $integration),
            default => null, // ai and anything else: never delivered this way.
        };
    }

    protected function shipEmail(Notification $notification, User $user, Integration $integration): void
    {
        if (empty($user->email)) {
            return;
        }

        $body = $notification->body ?: $notification->title;

        Email::for($integration->id)->mailer()->raw($body, function ($message) use ($user, $notification) {
            $message->to($user->email)->subject($notification->title);
        });

        $this->log('sent', $notification, $user, $integration);
    }

    protected function shipSms(Notification $notification, User $user, Integration $integration): void
    {
        if (empty($user->phone)) {
            return;
        }

        $text = trim($notification->title."\n".($notification->body ?? ''));

        $result = Sms::for($integration->id)->send($text, $user->phone);

        $this->log($result->ok ? 'sent' : 'failed', $notification, $user, $integration, $result->message);
    }

    protected function log(string $outcome, Notification $notification, User $user, Integration $integration, ?string $detail = null): void
    {
        Log::channel('integrations')->info('notification.'.$outcome, [
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'user_id' => $user->id,
            'integration_id' => $integration->id,
            'channel' => $integration->type?->code,
            'detail' => $detail,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::channel('integrations')->error('notification.ship-failed', [
            'notification_id' => $this->notificationId,
            'user_id' => $this->userId,
            'integration_id' => $this->integrationId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
