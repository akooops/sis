<?php

namespace App\Data\MenuItem;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * The whole tree as a flat, ordered list: dragging can move an item between
 * sibling groups, so parent_id travels with the position. Position within a
 * sibling group IS the order.
 *
 * `menu_id` scopes the payload — every id must already belong to that menu, so a
 * reorder can never adopt an item from another one.
 */
class ReorderMenuItemsData extends Data
{
    public function __construct(
        public string $menu_id,
        /** @var array<int, array{id: string, parent_id: string|null}> */
        public array $items,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $menuId = $context->payload['menu_id'] ?? null;

        return [
            'menu_id' => ['required', 'string', Rule::exists('menus', 'id')],

            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'string', 'distinct', Rule::exists('menu_items', 'id')->where('menu_id', $menuId)],
            'items.*.parent_id' => ['nullable', 'string', Rule::exists('menu_items', 'id')->where('menu_id', $menuId)],
        ];
    }

    /** Depth 2 across the payload — a per-field rule cannot see the other rows. */
    public static function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $validator->getData()['items'] ?? null;

            if (! is_array($items)) {
                return;
            }

            // The submitted roots: the only ids anything may be nested under.
            $roots = [];

            foreach ($items as $item) {
                if (is_array($item) && isset($item['id']) && ($item['parent_id'] ?? null) === null) {
                    $roots[$item['id']] = true;
                }
            }

            foreach ($items as $index => $item) {
                $parentId = is_array($item) ? ($item['parent_id'] ?? null) : null;

                if ($parentId === null) {
                    continue;
                }

                if ($parentId === ($item['id'] ?? null)) {
                    $validator->errors()->add("items.{$index}.parent_id", 'An item cannot be its own parent.');

                    continue;
                }

                if (! isset($roots[$parentId])) {
                    $validator->errors()->add("items.{$index}.parent_id", 'The parent must be a top-level item in the submitted list.');
                }
            }
        });
    }
}
