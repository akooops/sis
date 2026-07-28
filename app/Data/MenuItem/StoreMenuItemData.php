<?php

namespace App\Data\MenuItem;

use App\Models\MenuItem;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Create takes the default locale only; the rest come from the edit form.
 * No `order`: a new item goes last among its siblings, and reordering is its own
 * endpoint.
 */
class StoreMenuItemData extends Data
{
    public function __construct(
        public string $menu_id,
        public ?string $parent_id,
        public string $name,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        public string $title,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $rules = [
            'menu_id' => ['required', 'string', Rule::exists('menus', 'id')],

            // Depth 2: the parent must be a root item of the SAME menu.
            'parent_id' => [
                'nullable', 'string',
                Rule::exists('menu_items', 'id')
                    ->where('menu_id', $context->payload['menu_id'] ?? null)
                    ->whereNull('parent_id'),
            ],

            'name' => ['required', 'string', 'max:255'],

            // Both link kinds are optional, but never both at once — a parent is
            // often just a label.
            'url' => ['nullable', 'url', 'max:2048', 'prohibits:linkable_type,linkable_id'],
            'linkable_type' => ['nullable', 'required_with:linkable_id', 'string', Rule::in(MenuItem::LINKABLE_TYPES)],
            'linkable_id' => ['nullable', 'required_with:linkable_type', 'string'],

            'title' => ['required', 'string', 'max:255'],
        ];

        // The table follows the alias; an unknown one already fails on linkable_type.
        if ($table = MenuItem::linkableTable($context->payload['linkable_type'] ?? null)) {
            $rules['linkable_id'][] = Rule::exists($table, 'id');
        }

        return $rules;
    }
}
