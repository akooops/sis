<?php

namespace App\Services\Integrations\Drivers;

use App\Contracts\Integrations\Driver;
use App\Contracts\Integrations\SendsMail;
use App\Data\Integration\FieldData;

/**
 * SMTP email driver. Builds a Laravel smtp transport config from the integration
 * config for the runtime mailer bridge.
 */
class SmtpDriver implements Driver, SendsMail
{
    public function code(): string
    {
        return 'smtp';
    }

    public function type(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'SMTP';
    }

    public function icon(): string
    {
        return 'ki-sms';
    }

    /**
     * @return array<int, FieldData>
     */
    public function schema(): array
    {
        return [
            new FieldData(key: 'host', label: 'Host', type: 'text', required: true),
            // The one number here that IS integer-stepped: a TCP port is a whole
            // 16-bit number, so step 1 is deliberate rather than the browser's
            // accidental default.
            new FieldData(key: 'port', label: 'Port', type: 'number', required: true, default: 587, min: 1, max: 65535, step: 1),
            new FieldData(key: 'encryption', label: 'Encryption', type: 'select', default: 'tls', options: [
                ['value' => 'tls', 'label' => 'TLS'],
                ['value' => 'ssl', 'label' => 'SSL'],
                ['value' => 'none', 'label' => 'None'],
            ]),
            new FieldData(key: 'username', label: 'Username', type: 'text'),
            new FieldData(key: 'password', label: 'Password', type: 'password', secret: true),
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
        $encryption = $config['encryption'] ?? 'tls';

        return [
            'transport' => 'smtp',
            'host' => $config['host'] ?? null,
            'port' => (int) ($config['port'] ?? 587),
            'encryption' => $encryption === 'none' ? null : $encryption,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
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
