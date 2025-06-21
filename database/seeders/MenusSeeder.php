<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use App\Models\Page;
use Illuminate\Support\Str;

class MenusSeeder extends Seeder
{
    public function run()
    {
        $systemMenus = [
            [
                'name' => 'header_primary_menu',
                'is_system_menu' => true,
            ],
            [
                'name' => 'footer_primary_menu',
                'is_system_menu' => true,
            ]
        ];

        foreach ($systemMenus as $menuData) {
            $menu = Menu::updateOrCreate(
                ['name' => $menuData['name']],
                [
                    'name' => $menuData['name'],
                    'is_system_menu' => $menuData['is_system_menu'],
                ]
            );
        }
    }
}
