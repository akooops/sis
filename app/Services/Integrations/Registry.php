<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\Driver;
use App\Data\Integration\FieldData;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Maps a driver `code` to its class from config('integrations.drivers') and
 * resolves it through the container — the one place a driver is looked up, so a
 * new vendor is a class plus a config line, nothing else. Also owns the two
 * schema helpers the controllers use to fold submitted fields into the single
 * encrypted `config` array and to split it back for reading.
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
     * Fold submitted field values into the single config array. On update we start
     * from the stored config and overlay; a blank/absent secret is left untouched
     * (so the stored one survives). On store, missing non-secret fields fall back
     * to their declared default.
     *
     * @param  array<int, FieldData>  $schema
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $submitted
     * @return array<string, mixed>
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
     * Split a stored config into the non-secret values (for the edit form) and a
     * boolean map of which secret fields are set (never the secret value itself).
     *
     * @param  array<int, FieldData>  $schema
     * @param  array<string, mixed>  $config
     * @return array{0: array<string, mixed>, 1: array<string, bool>}
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

    /**
     * Laravel validation rules for a driver's schema, keyed `settings.<key>`, so
     * the store/update Data classes validate the exact shape the form submits.
     *
     * @param  array<int, FieldData>  $schema
     * @return array<string, array<int, mixed>>
     */
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
        // On update every field is optional (a blank secret keeps the stored
        // value; an omitted field keeps its config). On store, required fields
        // must be present.
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
