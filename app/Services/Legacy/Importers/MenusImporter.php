<?php

namespace App\Services\Legacy\Importers;

use App\Models\Menu;
use App\Services\Legacy\LegacyImporter;
use Illuminate\Support\Str;

/**
 * The menu ROWS only. Their items are a separate module that runs last — see
 * MenuItemsImporter for why.
 *
 * THE CODE IS THE WHOLE JOB. The old app addressed a menu by its auto-increment
 * id; this one addresses it by a frozen `code` that SiteContext reads directly
 * (`$site->menuItems('footer_primary')`). So a legacy menu has to be TOLD which
 * of this app's menus it is, and config('legacy.menus') is where that is written.
 *
 * A menu whose name is not in that map keeps a slugified version of its own name
 * as its code and is imported as a NON-system menu — which is exactly what an
 * unlisted menu is: one an admin made, not one the site resolves by name.
 *
 * Matching is by code, so a legacy `header_primary_menu` UPDATES the seeded
 * `header_primary` rather than creating a second header nobody renders.
 */
class MenusImporter extends LegacyImporter
{
    public function module(): string
    {
        return 'menus';
    }

    public function describe(): string
    {
        return 'Menus (items come last, as `menu-items`)';
    }

    public function sources(): array
    {
        return ['menus'];
    }

    public function run(): void
    {
        $this->each('menus', function (object $row) {
            $code = $this->code($row);

            $model = $this->model(Menu::class, 'menus', (int) $row->id, ['code' => $code]);

            // An existing seeded menu keeps the name this app gave it — "Header —
            // primary" reads better in the admin than "header_primary_menu", and
            // the code is what anything mechanical uses anyway.
            $model->name = $model->exists ? $model->name : (string) $row->name;
            $model->code = $code;

            // is_system is this app's judgement about what the site resolves by
            // name, never the old install's. Never demoted.
            $model->is_system = (bool) $model->is_system;

            $model->created_at = $row->created_at ?? $model->created_at;

            $this->save($model, 'menus', (int) $row->id);
        });
    }

    /** The frozen code for a legacy menu, mapped or derived. */
    protected function code(object $row): string
    {
        $name = trim((string) $row->name);
        $mapped = config('legacy.menus.'.$name);

        if (is_string($mapped)) {
            return $mapped;
        }

        $this->c->note(
            "Legacy menu [{$name}] is not in config('legacy.menus') — imported as an ordinary admin menu. "
            .'Add it there if the site is meant to resolve it by code.'
        );

        return Str::of($name)->slug('_')->value() ?: 'menu_'.$row->id;
    }
}
