<?php

namespace App\Services\Forms\FieldTypes;

use App\Data\Integration\FieldData;
use App\Models\FormField;

/**
 * A dividing line, with an optional caption sitting on it.
 *
 * NOT A HEADING. A heading names the thing that follows; a separator says "that
 * part is finished". On a long single-page form — a CV block, then education,
 * then skills — the eye needs the break more than it needs another title, and
 * stacking a heading on every section makes the form read as five forms.
 *
 * Captures nothing, so it never appears in an answer, a CSV column or a webhook
 * payload. Its caption lives in `content`, like the other display elements.
 */
class SeparatorType extends BaseFieldType
{
    public function code(): string
    {
        return 'separator';
    }

    public function label(): string
    {
        return 'Separator';
    }

    public function icon(): string
    {
        return 'ki-minus';
    }

    public function group(): string
    {
        return 'display';
    }

    public function isInput(): bool
    {
        return false;
    }

    /**
     * `content` only. There is no label to translate — the caption IS the
     * content, and a separator with both would be asking for the same string
     * twice.
     *
     * @return array<int, string>
     */
    public function translatable(): array
    {
        return ['content'];
    }

    /**
     * @return array<int, FieldData>
     */
    public function settings(): array
    {
        return [
            new FieldData(
                key: 'spacing',
                label: 'Space around it',
                type: 'select',
                default: 'normal',
                options: [
                    ['value' => 'tight', 'label' => 'Tight'],
                    ['value' => 'normal', 'label' => 'Normal'],
                    ['value' => 'loose', 'label' => 'Loose'],
                ],
            ),
        ];
    }

    /**
     * Nothing to validate — a separator has no answer.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormField $field, array $context = []): array
    {
        return [];
    }
}
