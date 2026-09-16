<?php

namespace Database\Seeders;

use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuAddonSeeder extends Seeder
{
    /**
     * Attach catalog add-on groups to existing menus, copying each group per menu.
     */
    public function run(): void
    {
        $path = database_path('data/menu-addons.php');

        if (! is_readable($path)) {
            throw new RuntimeException("Menu add-ons catalog not found or unreadable at {$path}");
        }

        /** @var list<array<string, mixed>> $groups */
        $groups = require $path;

        $this->seedFrom($groups);
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     */
    public function seedFrom(array $groups): void
    {
        $groupsByMenu = $this->groupsByMenuName($groups);

        $missing = [];

        foreach (array_keys($groupsByMenu) as $menuName) {
            if (MenuModel::query()->where('name', $menuName)->doesntExist()) {
                $missing[] = $menuName;
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'Menu add-on catalog references unknown menu names: '.implode(', ', $missing),
            );
        }

        DB::transaction(function () use ($groupsByMenu): void {
            foreach ($groupsByMenu as $menuName => $menuGroups) {
                $menus = MenuModel::query()->where('name', $menuName)->get();

                foreach ($menus as $menu) {
                    $menu->addonGroups()->delete();
                    $this->createGroupsForMenu($menu, $menuGroups);
                }
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupsByMenuName(array $groups): array
    {
        /** @var array<string, list<array<string, mixed>>> $groupsByMenu */
        $groupsByMenu = [];

        foreach ($groups as $group) {
            $products = $group['products'] ?? [];

            if (! is_array($products) || $products === []) {
                throw new RuntimeException("Add-on group {$group['name']} has no products.");
            }

            $definition = [
                'name' => $group['name'],
                'is_required' => (bool) ($group['is_required'] ?? false),
                'selection_type' => $group['selection_type'],
                'options' => $group['options'],
            ];

            foreach ($products as $productName) {
                $productName = trim((string) $productName);

                if ($productName === '') {
                    continue;
                }

                $groupsByMenu[$productName][] = $definition;
            }
        }

        return $groupsByMenu;
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     */
    private function createGroupsForMenu(MenuModel $menu, array $groups): void
    {
        foreach ($groups as $groupIndex => $groupData) {
            $group = MenuAddonGroup::query()->create([
                'menu_id' => $menu->id,
                'name' => $groupData['name'],
                'selection_type' => $groupData['selection_type'],
                'is_required' => $groupData['is_required'],
                'sort_order' => $groupIndex,
            ]);

            foreach ($groupData['options'] as $optionIndex => $optionData) {
                MenuAddonOption::query()->create([
                    'menu_addon_group_id' => $group->id,
                    'name' => $optionData['name'],
                    'price' => $optionData['price'],
                    'is_available' => $optionData['is_available'] ?? true,
                    'sort_order' => $optionIndex,
                ]);
            }
        }
    }
}
