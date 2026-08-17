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
        /*
         * Bounds for a `number` field, enforced at BOTH ends: SchemaField puts
         * them on the input as min/max/step, fieldRules() turns them into
         * min:/max:/multiple_of:. Declared on any other type they are ignored by
         * both, because Laravel sizes a non-numeric value by its LENGTH — the
         * opposite of what these say.
         *
         * `step` is a union rather than a float plus a flag because 'any' is the
         * word the browser itself spells, and no number stands in for it: 0
         * makes every value invalid, and OMITTING step is what defaults it to 1
         * — the bug these exist to fix, a control built to hold 0.7 refusing to
         * accept it. PHP 8.1 has no literal types, so 'any' is the only string
         * a schema may put here and nothing but review enforces that.
         */
        public int|float|null $min = null,
        public int|float|null $max = null,
        public int|float|string|null $step = null,
    ) {}
}
