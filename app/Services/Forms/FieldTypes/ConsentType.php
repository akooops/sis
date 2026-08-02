<?php

namespace App\Services\Forms\FieldTypes;

use App\Models\FormField;

/**
 * A single yes/no tick — terms, privacy, opting in to a mailing list.
 *
 * Split from the checkbox group deliberately: it has one label rather than a
 * list of options, `required` means "must be ticked" rather than "must have a
 * value", and it exports as Yes/No instead of a comma-joined list.
 */
class ConsentType extends BaseFieldType
{
    public function code(): string
    {
        return 'consent';
    }

    public function label(): string
    {
        return 'Consent';
    }

    public function icon(): string
    {
        return 'ki-shield-tick';
    }

    public function group(): string
    {
        return 'choice';
    }

    /**
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['label'];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        // `accepted`, not `required`: a required boolean is satisfied by false,
        // which is the opposite of what a consent tick means.
        return ['' => [$field->is_required ? 'accepted' : 'nullable', 'boolean']];
    }

    public function store(FormField $field, mixed $value): mixed
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function display(FormField $field, mixed $value, ?string $locale = null): string
    {
        return $value ? 'Yes' : 'No';
    }
}
