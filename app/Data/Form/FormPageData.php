<?php

namespace App\Data\Form;

use App\Models\FormPage;
use Spatie\LaravelData\Data;

class FormPageData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $order,
        public bool $is_interstitial,
        public ?string $css_id,
        public ?string $css_class,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<int, FormFieldData> */
        public array $fields,
    ) {}

    public static function fromModel(FormPage $page): self
    {
        return new self(
            id: $page->id,
            name: $page->name,
            order: (int) $page->order,
            is_interstitial: (bool) $page->is_interstitial,
            css_id: $page->css_id,
            css_class: $page->css_class,
            title: $page->enabledTranslations('title'),
            // topLevelFields, because a group carries its own children and the
            // canvas lays out only what sits on the page. Loading `fields` here
            // would draw every child a second time as a loose card.
            fields: $page->relationLoaded('topLevelFields')
                ? $page->topLevelFields->map(fn ($f) => FormFieldData::from($f))->all()
                : [],
        );
    }
}
