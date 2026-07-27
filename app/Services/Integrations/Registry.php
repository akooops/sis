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
        // Update: everything optional (a blank secret keeps the stored value).
        // Store: required fields must be present.
        $rules = ! $forUpdate && $field->required ? ['required'] : ['sometimes', 'nullable'];

        $rules[] = match ($field->type) {
            'number' => 'numeric',
            'email' => 'email',
            'url' => 'url',
            'switch' => 'boolean',
            'select' => Rule::in(array_map(fn ($o) => $o['value'], $field->options ?? [])),
            default => 'string',
        };

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
