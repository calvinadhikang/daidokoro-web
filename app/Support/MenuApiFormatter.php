<?php

namespace App\Support;

use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;

class MenuApiFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function formatListItem(MenuModel $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'price' => $menu->price,
            'price_label' => 'Rp '.number_format($menu->price, 0, ',', '.'),
            'type' => 'standard',
            'is_available' => $menu->is_available,
            'is_recommended' => $menu->is_recommended,
            'image' => $menu->image,
            'categories' => $menu->categories
                ->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatDetail(MenuModel $menu): array
    {
        return [
            ...self::formatListItem($menu),
            'addon_groups' => $menu->addonGroups
                ->map(fn (MenuAddonGroup $group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'selection_type' => $group->selection_type,
                    'is_required' => $group->is_required,
                    'sort_order' => $group->sort_order,
                    'options' => $group->options
                        ->map(fn (MenuAddonOption $option) => [
                            'id' => $option->id,
                            'name' => $option->name,
                            'price' => $option->price,
                            'price_label' => 'Rp '.number_format($option->price, 0, ',', '.'),
                            'is_available' => $option->is_available,
                            'sort_order' => $option->sort_order,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'created_at' => $menu->created_at?->toIso8601String(),
            'updated_at' => $menu->updated_at?->toIso8601String(),
        ];
    }
}
