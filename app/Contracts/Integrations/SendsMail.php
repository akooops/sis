<?php

namespace App\Contracts\Integrations;

/**
 * A driver that can act as the app's mailer. The runtime bridge builds a Laravel
 * mailer config from mailerConfig() + mailFrom() when this driver's integration
 * is the active email one.
 */
interface SendsMail
{
    /**
     * A Laravel `mail.mailers.*` transport config array (transport, host, port,
     * encryption, username, password, …) built from the integration's config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function mailerConfig(array $config): array;

    /**
     * The `mail.from` address/name for this integration.
     *
     * @param  array<string, mixed>  $config
     * @return array{address: string|null, name: string|null}
     */
    public function mailFrom(array $config): array;
}
