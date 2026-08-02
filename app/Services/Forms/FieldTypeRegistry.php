<?php

namespace App\Services\Forms;

use App\Contracts\Forms\FieldType;
use App\Data\Integration\FieldData;
use Illuminate\Contracts\Container\Container;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Maps an element `code` to its class from config('forms.field_types') and
 * resolves it — the one place an element is looked up, so a new element is a
 * class plus a config line.
 *
 * Modelled on App\Services\Integrations\Registry, down to the settings-rule
 * helper, because the two solve the same problem: a server-declared schema the
 * frontend renders generically.
 */
class FieldTypeRegistry
{
    /** @var array<string, FieldType>|null resolved instances, by code */
    protected ?array $types = null;

    public function __construct(protected Container $app) {}

    /** @return array<string, FieldType> */
    public function all(): array
    {
        if ($this->types !== null) {
            return $this->types;
        }

        $this->types = [];

        foreach (config('forms.field_types', []) as $class) {
            /** @var FieldType $type */
            $type = $this->app->make($class);
            $this->types[$type->code()] = $type;
        }

        return $this->types;
    }

    public function has(string $code): bool
    {
        return array_key_exists($code, $this->all());
    }

    public function type(string $code): FieldType
    {
        return $this->all()[$code] ?? throw new InvalidArgumentException("Unknown form field type [{$code}].");
    }

    /** @return array<int, string> */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    /**
     * Codes that capture an answer. FormField::scopeCapturing() filters on this,
     * so anything that stores a value must report isInput() === true.
     *
     * @return array<int, string>
     */
    public function inputCodes(): array
    {
        return array_keys(array_filter($this->all(), fn (FieldType $type) => $type->isInput()));
    }

    /**
     * The builder palette: everything an admin can drop onto a canvas, with the
     * schema its inspector renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function palette(): array
    {
        return array_values(array_map(fn (FieldType $type) => [
            'code' => $type->code(),
            'label' => $type->label(),
            'icon' => $type->icon(),
            'group' => $type->group(),
            'is_input' => $type->isInput(),
            'has_options' => $type->hasOptions(),
            'translatable' => $type->translatable(),
            'settings' => array_map(fn (FieldData $f) => $f->toArray(), $type->settings()),
            'validations' => array_map(fn (FieldData $f) => $f->toArray(), $type->validations()),
        ], $this->all()));
    }

    /**
     * Rules for an element's own settings/validation blobs, keyed by the shape
     * the builder posts.
     *
     * @return array<string, array<int, mixed>>
     */
    public function settingsRules(string $code, string $prefix = 'settings'): array
    {
        if (! $this->has($code)) {
            return [];
        }

        $type = $this->type($code);

        $rules = [];

        foreach ($type->settings() as $field) {
            foreach ($this->fieldRules($field, "{$prefix}.{$field->key}") as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        foreach ($type->validations() as $field) {
            foreach ($this->fieldRules($field, "validation.{$field->key}") as $key => $rule) {
                $rules[$key] = $rule;
            }
        }

        return $rules;
    }

    /**
     * The rules for ONE declared field, keyed by the path it is posted at.
     *
     * A map rather than a plain list because a `multiselect` needs two entries —
     * the array itself and its elements.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function fieldRules(FieldData $field, string $key): array
    {
        // A multiselect stores an ARRAY of option values, so the membership rule
        // belongs on the ELEMENTS: Rule::in on the array itself compares the
        // whole array against one option and refuses every non-empty answer.
        // Without the `.*` rule the array would be accepted with anything in it,
        // which for FileType would mean an extension the uploader never allows.
        if ($field->type === 'multiselect') {
            return [
                $key => ['sometimes', 'nullable', 'array'],
                "{$key}.*" => ['string', $this->optionRule($field)],
            ];
        }

        $rule = match ($field->type) {
            'number' => 'numeric',
            'email' => 'email',
            'url' => 'url',
            'switch' => 'boolean',
            'tags' => 'array',
            'select' => $this->optionRule($field),
            default => 'string',
        };

        return [$key => ['sometimes', 'nullable', $rule]];
    }

    /** Membership of the values the field declared. */
    protected function optionRule(FieldData $field): mixed
    {
        return Rule::in(array_map(fn ($o) => $o['value'], $field->options ?? []));
    }
}
