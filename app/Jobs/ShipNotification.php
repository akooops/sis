<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Notification;
use App\Models\User;
use App\Services\Integrations\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Ships one notification to one user through one email integration. Dispatched by
 * NotificationService after the in-app row is already written, so a provider
 * being slow or down never blocks the notification itself.
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

        // Groups only carry email integrations, but that constraint lives in
        // validation — re-check here so a stray row can never mis-ship.
        if ($integration->type?->code !== 'email' || empty($user->email)) {
            return;
        }

        $body = $notification->body ?: $notification->title;

        Email::for($integration->id)->mailer()->raw($body, function ($message) use ($user, $notification) {
            $message->to($user->email)->subject($notification->title);
        });

        $this->log('sent', $notification, $user, $integration);
    }

    protected function log(string $outcome, Notification $notification, User $user, Integration $integration, ?string $detail = null): void
    {
        Log::channel('integrations')->info('notification.'.$outcome, [
            'notification_id' => $notification->id,
            'type' => $notification->type?->code,
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
