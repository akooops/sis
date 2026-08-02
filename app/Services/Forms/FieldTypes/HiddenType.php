<?php

namespace App\Services\Forms\FieldTypes;

/**
 * An answer the visitor never sees.
 *
 * An INPUT, not a display element: it carries a value into the submission, so it
 * has to be validated, normalised and exported like any other answer. The admin
 * sets that value in the builder, and it is the only translatable attribute here
 * — a hidden field shows nothing, so a label, a placeholder or help text would
 * be copy nobody could ever read.
 */
class HiddenType extends BaseFieldType
{
    public function code(): string
    {
        return 'hidden';
    }

    public function label(): string
    {
        return 'Hidden field';
    }

    public function icon(): string
    {
        return 'ki-eye-slash';
    }

    /**
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['value'];
    }
}
