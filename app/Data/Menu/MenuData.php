<?php

namespace App\Data\Menu;

use App\Models\Menu;
use Spatie\LaravelData\Data;

/** `items_count` comes from withCount — the index lists it, the tree is its own endpoint. */
class MenuData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $code,
        public bool $is_system,
        public int $items_count,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(Menu $menu): self
    {
        return new self(
            id: $menu->id,
            name: $menu->name,
            code: $menu->code,
            is_system: $menu->is_system,
            items_count: $menu->items_count ?? 0,
            created_at: $menu->created_at?->toIso8601String(),
            updated_at: $menu->updated_at?->toIso8601String(),
        );
    }
}
