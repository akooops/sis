<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/** A single-line text answer. */
class TextType extends BaseFieldType
{
    public function code(): string
    {
        return 'text';
    }

    public function label(): string
    {
        return 'Text';
    }

    public function icon(): string
    {
        return 'ki-abstract-14';
    }

    /**
     * @return array<int, FieldData>
     */
    public function validations(): array
    {
        return array_merge($this->lengthValidations(), [
            /*
             * Validated when the ADMIN saves it, not here: an arbitrary regex
             * run against visitor input on a public endpoint is a denial of
             * service via catastrophic backtracking. The save-time check
             * compiles it and caps its length.
             */
            new FieldData(key: 'pattern', label: 'Pattern', type: 'text', help: 'Optional regular expression, without delimiters.'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        $rules = $this->withLength($field, [$this->presence($field), 'string']);

        if ($pattern = $this->option($field, 'pattern')) {
            // Delimit and anchor here so an admin never has to, and so a stray
            // delimiter in their input cannot change the rule's meaning.
            $rules[] = 'regex:/^(?:'.$pattern.')$/u';
        }

        return ['' => $rules];
    }
}
