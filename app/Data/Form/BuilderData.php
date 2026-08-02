<?php

namespace App\Data\Form;

use App\Models\Form;
use App\Models\Language;
use Spatie\LaravelData\Data;

/**
 * The whole tree, in one request.
 *
 * The builder needs every page, field and option before it can render anything,
 * and paginating a form's structure would be absurd — so this is one call rather
 * than four. It also carries the locale list, so the canvas's language switcher
 * does not need a second round-trip on every open.
 */
class BuilderData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $status,
        public bool $is_system,
        public bool $is_locked,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        /** @var array<string, string|null> */
        public array $confirmation_message,
        public ?string $css_url,
        public ?string $custom_css,
        public string $default_locale,
        /** @var array<int, array{code: string, name: string, is_default: bool, is_rtl: bool}> */
        public array $locales,
        /** @var array<int, FormPageData> */
        public array $pages,
        public int $submissions_count,
    ) {}

    public static function fromModel(Form $form): self
    {
        $locales = Language::query()
            ->where('is_enabled', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['code', 'name', 'is_default', 'is_rtl'])
            ->map(fn ($language) => [
                'code' => $language->code,
                'name' => $language->name,
                'is_default' => (bool) $language->is_default,
                'is_rtl' => (bool) $language->is_rtl,
            ])
            ->all();

        return new self(
            id: $form->id,
            name: $form->name,
            slug: $form->slug,
            status: $form->status->getValue(),
            is_system: (bool) $form->is_system,
            // Named separately from is_system so the reason can change later
            // without the client having to learn a new rule.
            is_locked: $form->isLocked(),
            title: $form->enabledTranslations('title'),
            description: $form->enabledTranslations('description'),
            content: $form->enabledTranslations('content'),
            confirmation_message: $form->enabledTranslations('confirmation_message'),
            css_url: $form->css_url,
            custom_css: $form->custom_css,
            default_locale: Language::defaultCode(),
            locales: $locales,
            pages: $form->pages->map(fn ($page) => FormPageData::from($page))->all(),
            submissions_count: (int) $form->submissions_count,
        );
    }
}
