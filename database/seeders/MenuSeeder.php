<?php

namespace Database\Seeders;

use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed the application's menu data.
     */
    public function run(): void
    {
        $sundae = MenuModel::query()->create([
            'name' => 'Sundae',
            'image' => null,
            'price' => 6,
            'is_available' => true,
            'is_recommended' => true,
        ]);

        $size = MenuAddonGroup::query()->create([
            'menu_id' => $sundae->id,
            'name' => 'Size',
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        foreach ([
            ['name' => 'Small', 'price' => 0, 'sort_order' => 0],
            ['name' => 'Medium', 'price' => 1, 'sort_order' => 1],
            ['name' => 'Big', 'price' => 2, 'sort_order' => 2],
        ] as $option) {
            MenuAddonOption::query()->create([
                'menu_addon_group_id' => $size->id,
                ...$option,
            ]);
        }

        $toppings = MenuAddonGroup::query()->create([
            'menu_id' => $sundae->id,
            'name' => 'Toppings',
            'selection_type' => 'multiple',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        foreach ([
            ['name' => 'Choco sauce', 'price' => 1, 'sort_order' => 0],
            ['name' => 'Strawberry jam', 'price' => 1, 'sort_order' => 1],
            ['name' => 'Oreo cookies', 'price' => 1, 'sort_order' => 2],
        ] as $option) {
            MenuAddonOption::query()->create([
                'menu_addon_group_id' => $toppings->id,
                ...$option,
            ]);
        }

        $coffee = MenuModel::query()->create([
            'name' => 'Iced Coffee',
            'image' => null,
            'price' => 4,
            'is_available' => true,
        ]);

        $coffeeSize = MenuAddonGroup::query()->create([
            'menu_id' => $coffee->id,
            'name' => 'Size',
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        foreach ([
            ['name' => 'Small', 'price' => 0, 'sort_order' => 0],
            ['name' => 'Medium', 'price' => 1, 'sort_order' => 1],
            ['name' => 'Big', 'price' => 1, 'sort_order' => 2],
        ] as $option) {
            MenuAddonOption::query()->create([
                'menu_addon_group_id' => $coffeeSize->id,
                ...$option,
            ]);
        }
    }
}
