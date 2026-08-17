<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\Driver;
use App\Data\Integration\FieldData;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Maps a driver `code` to its class from config('integrations.drivers') and
 * resolves it — the one place a driver is looked up, so a new vendor is a class
 * plus a config line. Also owns the schema helpers that fold submitted fields
 * into the encrypted `config` array and split it back for reading.
 */
class Registry
{
    /** @var array<string, Driver>|null resolved instances, by code */
    protected ?array $drivers = null;

    public function __construct(protected Container $app) {}

    /** @return array<string, Driver> */
    public function all(): array
    {
        if ($this->drivers !== null) {
            return $this->drivers;
        }

        $this->drivers = [];

        foreach (config('integrations.drivers', []) as $class) {
            /** @var Driver $driver */
            $driver = $this->app->make($class);
            $this->drivers[$driver->code()] = $driver;
        }

        return $this->drivers;
    }

    public function has(string $code): bool
    {
        return array_key_exists($code, $this->all());
    }

    public function driver(string $code): Driver
    {
        return $this->all()[$code] ?? throw new InvalidArgumentException("Unknown integration driver [{$code}].");
    }

    /**
     * Fold submitted values into the config array. On update, start from the stored
     * config and overlay — a blank secret is left alone. On store, missing
     * non-secret fields fall back to their declared default.
     */
    public function applyValues(array $schema, array $existing, array $submitted, bool $forUpdate): array
    {
        $config = $forUpdate ? $existing : [];

        foreach ($schema as $field) {
            $provided = array_key_exists($field->key, $submitted);
            $value = $provided ? $submitted[$field->key] : null;

            if ($field->secret) {
                if ($value !== null && $value !== '') {
                    $config[$field->key] = $value;
                }

                continue;
            }

            if ($provided) {
                $config[$field->key] = $this->cast($field, $value);
            } elseif (! $forUpdate && $field->default !== null) {
                $config[$field->key] = $field->default;
            }
        }

        return $config;
    }

    /**
     * Split a stored config into plain values for the form plus a boolean map of
     * which secrets are set. Never the secret itself.
     */
    public function splitForRead(array $schema, array $config): array
    {
        $nonSecret = [];
        $secretsSet = [];

        foreach ($schema as $field) {
            if ($field->secret) {
                $secretsSet[$field->key] = filled($config[$field->key] ?? null);
            } elseif (array_key_exists($field->key, $config)) {
                $nonSecret[$field->key] = $config[$field->key];
            }
        }

        return [$nonSecret, $secretsSet];
    }

    /** Rules for a driver's schema, keyed `settings.<key>` — the shape the form posts. */
    public function rulesFor(array $schema, bool $forUpdate): array
    {
        $rules = [];

        foreach ($schema as $field) {
            $rules["settings.{$field->key}"] = $this->fieldRules($field, $forUpdate);
        }

        return $rules;
    }

    /**
     * @return array<int, mixed>
     */
    protected function fieldRules(FieldData $field, bool $forUpdate): array
    {
        /*
         * Store: required fields must be present and non-blank.
         *
         * Update: `sometimes` + `required` is NOT "required on update". Laravel
         * skips every rule on an attribute the payload does not carry, so a key
         * may still be OMITTED to leave the stored value alone — which is what
         * applyValues(forUpdate: true) does by starting from the stored config.
         * What the pair stops is sending the key BLANK, which used to overwrite a
         * working value with '' and half-configure the integration: a captcha with
         * no site key renders no widget yet still demands a token, an SMTP row
         * with no host cannot send, a GA4 row with no measurement id draws no tag.
         * No driver has a required non-secret field that is legitimately blank, so
         * nothing legitimate is refused.
         *
         * Falsy values are safe: Laravel's `required` only rejects null, a
         * whitespace-only string and an empty array/Countable, so 0, '0' and false
         * all pass it — a required number (SMTP port) or switch is not caught out.
         *
         * Secrets stay nullable on update because blank is how the form says "keep
         * the stored one" (see applyValues()).
         */
        if ($field->required) {
            $rules = ! $forUpdate ? ['required'] : ($field->secret ? ['sometimes', 'nullable'] : ['sometimes', 'required']);
        } else {
            $rules = ['sometimes', 'nullable'];
        }

        $rules[] = match ($field->type) {
            'number' => 'numeric',
            'email' => 'email',
            'url' => 'url',
            'switch' => 'boolean',
            'select' => Rule::in(array_map(fn ($o) => $o['value'], $field->options ?? [])),
            default => 'string',
        };

        /*
         * Bounds, after the type rule and for the same reason `pattern` is after
         * it: on a value that is not a number at all, "must be a number" is the
         * message worth reporting first. They also only MEAN magnitude once
         * `numeric` is in the set — Laravel sizes a value by the rules it
         * carries, so min:0 on an unruled string measures its length instead.
         * Hence the type gate: a bound silently becoming a length rule on some
         * other field is worse than being ignored there.
         *
         * multiple_of is the server half of the input's `step`, so a stepped
         * field cannot be bypassed by posting past the control; 'any' is the
         * HTML keyword for no stepping and has no rule to add. It compares with
         * BigDecimal, so 587 against a step of 1 is exact, not float-fuzzy.
         */
        if ($field->type === 'number') {
            if ($field->min !== null) {
                $rules[] = 'min:'.$field->min;
            }

            if ($field->max !== null) {
                $rules[] = 'max:'.$field->max;
            }

            if ($field->step !== null && $field->step !== 'any') {
                $rules[] = 'multiple_of:'.$field->step;
            }
        }

        // Shape, when the driver declares one. Last, so the type rule still
        // reports first on a value that is not even a string.
        if ($field->pattern !== null) {
            $rules[] = 'regex:'.$field->pattern;
        }

        return $rules;
    }

    protected function cast(FieldData $field, mixed $value): mixed
    {
        return match ($field->type) {
            'number' => $value === null || $value === '' ? null : (str_contains((string) $value, '.') ? (float) $value : (int) $value),
            'switch' => (bool) $value,
            default => $value,
        };
    }
}
