<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\SendsMail;
use App\Models\Integration;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Support\Facades\Mail as MailFacade;
use RuntimeException;

/**
 * Email channel. Consistent with Sms and Ai:
 *   Email::default()->send($mailable);
 *   Email::for('Transactional')->mailer();
 *
 * The global default mailer is already swapped to the active email integration by
 * AppServiceProvider::useIntegrationMailer(); this is for pinning a specific one.
 * Config is set per-request only — never persisted to .env or cached config.
 */
class Email
{
    public function __construct(protected ?Integration $integration) {}

    public static function default(): self
    {
        return new self(Integration::activeFor('email'));
    }

    public static function for(string $idOrName): self
    {
        return new self(Integration::query()
            ->ofType('email')
            ->where(fn ($q) => $q->whereKey($idOrName)->orWhere('name', $idOrName))
            ->firstOrFail());
    }

    public function mailer(): MailerContract
    {
        if (! $this->integration) {
            throw new RuntimeException('No email integration is configured.');
        }

        $driver = $this->integration->resolveDriver();

        if (! $driver instanceof SendsMail) {
            throw new RuntimeException('The integration cannot send mail.');
        }

        $name = 'integration_'.$this->integration->id;
        config(["mail.mailers.{$name}" => $driver->mailerConfig($this->integration->config ?? [])]);

        return MailFacade::mailer($name);
    }

    public function send($mailable): void
    {
        $this->mailer()->send($mailable);
    }
}
