<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * The fallback category. Nothing is ever unfiled: a record with no category
 * picked gets this one, and deleting a category moves its content here.
 *
 * `is_default` is the lock — the controller refuses to delete it and the observer
 * refuses to leave nothing default. Everything else stays editable: rename it
 * "General", recolour it, translate it.
 *
 * firstOrCreate, like PagesSeeder: this is editorial content, so a reseed must
 * not clobber a name an admin changed. Only the lock is re-asserted.
 */
class CategoriesSeeder extends Seeder
{
    /** One per locale in LanguagesSeeder, so enabling one later needs no edit. */
    protected array $title = [
        'en' => 'Uncategorized',
        'ar' => 'غير مصنف',
        'fr' => 'Non classé',
        'es' => 'Sin categoría',
        'de' => 'Nicht kategorisiert',
        'it' => 'Senza categoria',
        'pt' => 'Sem categoria',
        'ru' => 'Без категории',
        'hi' => 'अवर्गीकृत',
    ];

    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['code' => 'uncategorized'],
            [
                'name' => 'Uncategorized',
                'title' => $this->title,
                'color' => '#5C5C5C',
                'is_default' => true,
            ],
        );

        if (! $category->wasRecentlyCreated && ! Category::default()) {
            $category->forceFill(['is_default' => true])->save();
        }
    }
}
