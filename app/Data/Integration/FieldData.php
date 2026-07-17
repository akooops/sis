<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/**
 * One field a driver declares in its credential/config schema. The admin form is
 * rendered from a list of these, and the server validation is generated from
 * them too, so the PHP driver and the Svelte form can never drift.
 *
 * `secret: true` marks a field whose value is a credential — it is stored in the
 * encrypted `credentials` blob, never returned to the client (only a
 * "secrets_set" boolean), and only overwritten when a new value is submitted.
 */
class FieldData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        // text | password | number | email | url | select | switch | textarea
        public string $type = 'text',
        public bool $required = false,
        public bool $secret = false,
        public mixed $default = null,
        /** @var array<int, array{value: mixed, label: string}>|null */
        public ?array $options = null,
        public ?string $help = null,
    ) {}
}
