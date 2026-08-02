<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/**
 * A navigation control.
 *
 * `goto` is what makes instruction pages work: it jumps to the page named by
 * form_fields.target_form_page_id and pushes the current page onto a stack, so
 * `back` returns to wherever the visitor came from rather than to a fixed
 * previous page.
 *
 * `back` and `previous` are the same operation — pop the stack — and are
 * deliberately one action rather than two, so they cannot disagree about where
 * "back" means.
 */
class ButtonType extends BaseFieldType
{
    public function code(): string
    {
        return 'button';
    }

    public function label(): string
    {
        return 'Button';
    }

    public function icon(): string
    {
        return 'ki-click';
    }

    public function group(): string
    {
        return 'action';
    }

    public function isInput(): bool
    {
        return false;
    }

    /**
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['label'];
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(key: 'action', label: 'Action', type: 'select', required: true, default: 'next', options: [
                ['value' => 'next', 'label' => 'Next page'],
                ['value' => 'back', 'label' => 'Back'],
                ['value' => 'goto', 'label' => 'Go to a page'],
                ['value' => 'submit', 'label' => 'Submit the form'],
            ], help: 'Back returns to whichever page the visitor came from, which is what lets an instruction page be entered from anywhere.'),
            new FieldData(key: 'variant', label: 'Style', type: 'select', default: 'primary', options: [
                ['value' => 'primary', 'label' => 'Primary'],
                ['value' => 'secondary', 'label' => 'Secondary'],
            ]),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return [];
    }
}
