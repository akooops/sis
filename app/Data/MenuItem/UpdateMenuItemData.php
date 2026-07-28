<?php

namespace App\Data\MenuItem;

use App\Models\Language;
use App\Models\MenuItem;
use Closure;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * Every locale at once. Errors come back keyed `title.ar`.
 * No `order`: reordering is its own endpoint.
 */
class UpdateMenuItemData extends Data
{
    public function __construct(
        public string $menu_id,
        public ?string $parent_id,
        public string $name,
        public ?string $url,
        public ?string $linkable_type,
        public ?string $linkable_id,
        /** @var array<string, string|null> */
        public array $title,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        $codes = Language::enabledCodes();
        $default = Language::defaultCode();

        $item = request()->route('menu_item');
        $item = $item instanceof MenuItem ? $item : null;

        // Depth 2: the parent must be a root item of the SAME menu, never itself.
        $parent = Rule::exists('menu_items', 'id')
            ->where('menu_id', $context->payload['menu_id'] ?? null)
            ->whereNull('parent_id');

        if ($item !== null) {
            $parent->whereNot('id', $item->getKey());
        }

        $rules = [
            'menu_id' => ['required', 'string', Rule::exists('menus', 'id')],

            'parent_id' => [
                'nullable', 'string',
                $parent,
                // Nesting a parent would put its children at depth 3.
                function (string $attribute, mixed $value, Closure $fail) use ($item) {
                    if ($value !== null && $item?->children()->exists()) {
                        $fail('This item has children of its own and cannot be nested under another item.');
                    }
                },
            ],

            'name' => ['required', 'string', 'max:255'],

            // Both link kinds are optional, but never both at once. Sending null
            // for all three clears the link.
            'url' => ['nullable', 'url', 'max:2048', 'prohibits:linkable_type,linkable_id'],
            'linkable_type' => ['nullable', 'required_with:linkable_id', 'string', Rule::in(MenuItem::LINKABLE_TYPES)],
            'linkable_id' => ['nullable', 'required_with:linkable_type', 'string'],

            // array:en,ar also rejects unknown keys.
            'title' => ['required', 'array:'.implode(',', $codes)],
        ];

        // The table follows the alias; an unknown one already fails on linkable_type.
        if ($table = MenuItem::linkableTable($context->payload['linkable_type'] ?? null)) {
            $rules['linkable_id'][] = Rule::exists($table, 'id');
        }

        // Per locale, not `title.*` plus an override: merged rule sets read badly.
        foreach ($codes as $code) {
            $rules["title.{$code}"] = $code === $default
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
