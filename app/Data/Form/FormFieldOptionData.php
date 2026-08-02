<?php

namespace App\Data\Form;

use App\Models\FormFieldOption;
use Spatie\LaravelData\Data;

/** `value` is the stored answer and is never translated; `label` is. */
class FormFieldOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $value,
        public int $order,
        public bool $is_default,
        /** @var array<string, string|null> */
        public array $label,
    ) {}

    public static function fromModel(FormFieldOption $option): self
    {
        return new self(
            id: $option->id,
            value: $option->value,
            order: (int) $option->order,
            is_default: (bool) $option->is_default,
            label: $option->enabledTranslations('label'),
        );
    }
}
