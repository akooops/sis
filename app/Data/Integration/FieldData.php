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
        /*
         * text | password | number | email | url | select | multiselect | switch
         * | textarea | tags | color
         *
         * `select` and `multiselect` are both driven by `options` and both refuse
         * anything outside them; `multiselect` stores an ARRAY of those values.
         * `tags` is the free-text one — it takes no options, so a field whose
         * answers come from a fixed list must be a multiselect, not a tags box
         * the admin can type anything into.
         */
        public string $type = 'text',
        public bool $required = false,
        public bool $secret = false,
        public mixed $default = null,
        /** @var array<int, array{value: mixed, label: string}>|null */
        public ?array $options = null,
        public ?string $help = null,
        /**
         * A delimited regex the submitted value must match, e.g. a GA4
         * measurement id. Server-side only — SchemaField ignores keys it does
         * not know, so declaring one costs no frontend work.
         */
        public ?string $pattern = null,
    ) {}
}
