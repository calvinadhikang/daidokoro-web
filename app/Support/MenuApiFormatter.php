<?php

namespace App\Support;

use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\SalesChannel;

class MenuApiFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function formatListItem(MenuModel $menu, ?SalesChannel $channel = null): array
    {
        $price = $menu->effectivePrice($channel);
        $pricingType = $menu->pricing_type ?: MenuModel::PRICING_STANDARD;
        $isAvailable = $channel === null
            ? $menu->is_available
            : $menu->effectiveIsAvailable($channel);

        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'price' => $price,
            'price_label' => PriceLabel::format($price, $pricingType),
            'type' => $pricingType,
            'pricing_type' => $pricingType,
            'is_available' => $isAvailable,
            'is_recommended' => $menu->is_recommended,
            'image' => $menu->image,
            'price_override' => $menu->channelPivot($channel)?->price_override,
            'categories' => $menu->relationLoaded('categories')
                ? $menu->categories
                    ->map(fn ($category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                    ])
                    ->values()
                    ->all()
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatDetail(MenuModel $menu, ?SalesChannel $channel = null): array
    {
        $menu->loadMissing(['addonGroups.options', 'categories:id,name']);

        return [
            ...self::formatListItem($menu, $channel),
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
                            'price_label' => PriceLabel::format((int) $option->price),
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
