<?php

namespace App\Data\Integration;

use Spatie\LaravelData\Data;

/**
 * One field in a driver schema. The admin form and the server validation are
 * both generated from these, so PHP and Svelte cannot drift.
 *
 * secret: true means the value lives in the encrypted blob, is never returned
 * (only a secrets_set boolean), and is overwritten only when resubmitted.
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
