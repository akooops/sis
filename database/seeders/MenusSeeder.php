<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

/**
 * The four navigation menus the public site renders.
 *
 * The CODES are what matter: App\Services\Site\MenuBag resolves each slot from a
 * setting first and falls back to one of these codes. `is_system` freezes the
 * code (MenusController blocks the change and the delete) because the site looks
 * the menu up by that string.
 *
 * Codes are normalised against the old app's, which all ended `_menu` — noise on
 * a table called `menus`.
 *
 * MENUS ONLY, NO ITEMS. The rows are the structure the site depends on; what
 * goes in them is editorial and belongs to whoever runs the site. An empty menu
 * renders as an empty nav rather than as a guess, which is the same call
 * PagesSeeder and FormsSeeder make about content.
 */
class MenusSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'header_primary' => 'Header - primary menu',
            'header_cta' => 'Header - call to action menu',
            'header_services' => 'Header - services menu',
            'footer_primary' => 'Footer - primary menu',
            'footer_secondary' => 'Footer - secondary menu',
        ];

        foreach ($menus as $code => $name) {
            $menu = Menu::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_system' => true],
            );

            if (! $menu->wasRecentlyCreated && ! $menu->is_system) {
                $menu->forceFill(['is_system' => true])->save();
            }
        }
    }
}
