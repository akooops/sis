<?php

namespace App\Data\Page;

use App\Data\Menu\MenuData;
use App\Models\Page;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/** Translatable fields are full locale => value maps. `menu` is Lazy — included only when eager-loaded. */
class PageData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public ?string $menu_id,
        /** @var array<string, string|null> */
        public array $title,
        /** @var array<string, string|null> */
        public array $description,
        /** @var array<string, string|null> */
        public array $content,
        public string $status,
        public ?string $published_at,
        public ?string $css_url,
        public ?string $custom_css,
        public bool $is_system,
        public ?string $thumbnail_url,
        public ?string $created_at,
        public ?string $updated_at,
        public Lazy|MenuData|null $menu,
    ) {}

    public static function fromModel(Page $page): self
    {
        return new self(
            id: $page->id,
            name: $page->name,
            slug: $page->slug,
            menu_id: $page->menu_id,
            title: $page->enabledTranslations('title'),
            description: $page->enabledTranslations('description'),
            content: $page->enabledTranslations('content'),
            status: $page->status->getValue(),
            published_at: $page->published_at?->toIso8601String(),
            css_url: $page->css_url,
            custom_css: $page->custom_css,
            is_system: $page->is_system,
            thumbnail_url: $page->thumbnail_url,
            created_at: $page->created_at?->toIso8601String(),
            updated_at: $page->updated_at?->toIso8601String(),
            menu: Lazy::whenLoaded('menu', $page, fn () => $page->menu ? MenuData::from($page->menu) : null),
        );
    }
}
