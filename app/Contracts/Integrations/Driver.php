<?php

namespace App\Contracts\Integrations;

/**
 * A vendor implementation within a type (SMTP under email, OpenAI under ai). It
 * self-describes: type, code, label, icon, and the field schema the form is
 * built from. Registered in config('integrations.drivers'), mirrored into
 * integration_drivers, resolved through Registry.
 *
 * SendsMail/SendsSms/GeneratesText add the actual action, each receiving the
 * integration's decrypted config.
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
