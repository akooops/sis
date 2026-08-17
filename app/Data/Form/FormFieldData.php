<?php

namespace App\Data\Form;

use App\Models\FormField;
use Spatie\LaravelData\Data;

/**
 * One element, as the builder edits it.
 *
 * Translatable values are full locale maps rather than resolved strings: the
 * builder swaps the whole canvas between locales without refetching, and the
 * public renderer takes exactly the same shape.
 */
class FormFieldData extends Data
{
    public function __construct(
        public string $id,
        public string $form_page_id,
        public string $type,
        public string $key,
        public int $order,
        public bool $is_required,
        public bool $is_unique,
        /** @var array<string, mixed> */
        public array $settings,
        /** @var array<string, mixed> */
        public array $validation,
        public ?string $target_form_page_id,
        public ?string $css_id,
        public ?string $css_class,
        /** @var array<string, string|null> */
        public array $label,
        /** @var array<string, string|null> */
        public array $placeholder,
        /** @var array<string, string|null> */
        public array $value,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<int, FormFieldOptionData> */
        public array $options,
        /**
         * The elements inside a repeatable group, one level deep and empty for
         * everything else. Same shape as this class, so the builder canvas draws
         * a child with the same card it draws anything else.
         *
         * @var array<int, FormFieldData>
         */
        public array $children = [],
    ) {}

    public static function fromModel(FormField $field): self
    {
        return new self(
            id: $field->id,
            form_page_id: $field->form_page_id,
            type: $field->type,
            key: $field->key,
            order: (int) $field->order,
            is_required: (bool) $field->is_required,
            is_unique: (bool) $field->is_unique,
            settings: $field->settings ?? [],
            validation: $field->validation ?? [],
            target_form_page_id: $field->target_form_page_id,
            css_id: $field->css_id,
            css_class: $field->css_class,
            label: $field->enabledTranslations('label'),
            placeholder: $field->enabledTranslations('placeholder'),
            value: $field->enabledTranslations('value'),
            content: $field->enabledTranslations('content'),
            options: $field->relationLoaded('options')
                ? $field->options->map(fn ($o) => FormFieldOptionData::from($o))->all()
                : [],
            children: $field->relationLoaded('children')
                ? $field->children->map(fn ($c) => FormFieldData::from($c))->all()
                : [],
        );
    }
}
