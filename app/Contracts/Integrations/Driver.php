<?php

namespace App\Contracts\Integrations;

/**
 * A vendor implementation within an integration type (SMTP under email, OpenAI
 * under ai). A driver self-describes — its type, code, label, icon, and the field
 * schema the admin form is built from. Registered in config('integrations.drivers')
 * and mirrored into the integration_drivers table; resolved by code through
 * App\Services\Integrations\Registry.
 *
 * Capability contracts (SendsMail, SendsSms, GeneratesText) add the actual action,
 * and each receives the integration's full decrypted config array.
 */
interface Driver
{
    /** Stable machine code, e.g. 'smtp', 'openai'. */
    public function code(): string;

    /** The integration type this driver belongs to, e.g. 'email', 'sms', 'ai'. */
    public function type(): string;

    /** Human label for the driver picker. */
    public function label(): string;

    /** Keenicon class for the UI. */
    public function icon(): string;

    /**
     * The fields the admin fills in (credentials + config together).
     *
     * @return array<int, \App\Data\Integration\FieldData>
     */
    public function schema(): array;
}
