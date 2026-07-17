<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\SendsMail;
use App\Data\Integration\FieldData;

/**
 * Twilio (SendGrid) email driver. SendGrid exposes an SMTP relay, so this is just
 * an smtp transport with a fixed host and the API key as the password — no extra
 * package needed. Thin by design; optimise (native API transport) later.
 */
class TwilioEmailDriver implements Driver, SendsMail
{
    public function code(): string
    {
        return 'twilio';
    }

    public function type(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'Twilio (SendGrid)';
    }

    public function icon(): string
    {
        return 'ki-abstract-41';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            new FieldData(key: 'api_key', label: 'API key', type: 'password', required: true, secret: true, help: 'Your SendGrid API key.'),
            new FieldData(key: 'from_address', label: 'From address', type: 'email', required: true),
            new FieldData(key: 'from_name', label: 'From name', type: 'text'),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function mailerConfig(array $config): array
    {
        return [
            'transport' => 'smtp',
            'host' => 'smtp.sendgrid.net',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'apikey',
            'password' => $config['api_key'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{address: string|null, name: string|null}
     */
    public function mailFrom(array $config): array
    {
        return [
            'address' => $config['from_address'] ?? null,
            'name' => $config['from_name'] ?? null,
        ];
    }
}
