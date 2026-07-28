<?php

namespace App\Data\MenuItem;

use App\Data\Menu\MenuData;
use App\Enums\MorphType;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;

/**
 * `order` is read-only here — it is written by the reorder endpoint.
 * `linkable_type` is a MorphType alias, never a class name, and `linkable` is
 * the linked record inlined rather than one DTO per linkable model.
 */
class MenuItemData extends Data
{
    public function __construct(
        public string $id,
        public string $menu_id,
        public ?string $parent_id,
        public string $name,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        /** @var array<string, string|null> */
        public array $title,
        public int $order,
        public ?string $created_at,
        public ?string $updated_at,
        /** @var array<string, string|null>|null */
        public ?array $linkable,
        /** @var Lazy|array<int, MenuItemData> */
        public Lazy|array $children,
        public Lazy|MenuData|null $menu,
    ) {}

    public static function fromModel(MenuItem $item): self
    {
        return new self(
            id: $item->id,
            menu_id: $item->menu_id,
            parent_id: $item->parent_id,
            name: $item->name,
            url: $item->url,
            linkable_type: MorphType::aliasFor($item->linkable_type),
            linkable_id: $item->linkable_id,
            title: $item->enabledTranslations('title'),
            order: $item->order,
            created_at: $item->created_at?->toIso8601String(),
            updated_at: $item->updated_at?->toIso8601String(),
            linkable: static::link($item->linkable),
            // Depth 2, so this recurses exactly once: a child's children are never loaded.
            children: Lazy::whenLoaded('children', $item, fn () => static::collect($item->children->all())),
            menu: Lazy::whenLoaded('menu', $item, fn () => $item->menu ? MenuData::from($item->menu) : null),
        );
    }

    /**
     * name and slug are the two columns all eight linkable models share.
     *
     * @return array<string, string|null>|null
     */
    protected static function link(?Model $model): ?array
    {
        if ($model === null) {
            return null;
        }

        return [
            'type' => MorphType::aliasFor($model->getMorphClass()),
            'id' => $model->getKey(),
            'name' => $model->getAttribute('name'),
            'slug' => $model->getAttribute('slug'),
        ];
    }
}
