<?php

namespace App\Services;

use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Validation\ValidationException;

class MenuOrderLineBuilder
{
    /**
     * @param  array<int, int>  $addonOptionIds
     * @return array{
     *     menu_id: int,
     *     menu_name: string,
     *     quantity: int,
     *     unit_price: int,
     *     line_total: int,
     *     addon_option_ids: array<int, int>,
     *     addons: array<int, array<string, mixed>>
     * }
     */
    public function build(
        MenuModel $menu,
        int $quantity,
        array $addonOptionIds,
    ): array {
        $menu->loadMissing(['addonGroups.options']);

        $addonSnapshots = $this->resolveAddonSelections($menu, $addonOptionIds);
        $addonTotal = array_sum(array_column($addonSnapshots, 'price'));
        $unitPrice = $menu->price + $addonTotal;
        $lineTotal = $unitPrice * $quantity;

        return [
            'menu_id' => $menu->id,
            'menu_name' => $menu->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'addon_option_ids' => array_values(array_unique($addonOptionIds)),
            'addons' => $addonSnapshots,
        ];
    }

    /**
     * @param  array<int, int>  $selectedOptionIds
     * @return array<int, array<string, mixed>>
     */
    private function resolveAddonSelections(MenuModel $menu, array $selectedOptionIds): array
    {
        $selectedOptionIds = array_values(array_unique($selectedOptionIds));
        $validOptionIds = $menu->addonGroups
            ->flatMap(fn ($group) => $group->options->where('is_available', true))
            ->pluck('id')
            ->all();

        foreach ($selectedOptionIds as $optionId) {
            if (! in_array($optionId, $validOptionIds, true)) {
                throw ValidationException::withMessages([
                    'addon_option_ids' => 'One or more add-on options are unavailable or invalid for this menu.',
                ]);
            }
        }

        $selectedByGroup = [];

        foreach ($menu->addonGroups as $group) {
            $availableOptionIds = $group->options
                ->where('is_available', true)
                ->pluck('id')
                ->all();
            $groupOptionIds = $group->options->pluck('id')->all();
            $selectedForGroup = array_values(array_intersect($selectedOptionIds, $groupOptionIds));

            if ($group->is_required && $selectedForGroup === [] && $availableOptionIds !== []) {
                throw ValidationException::withMessages([
                    'addon_option_ids' => "Please select an option for {$group->name}.",
                ]);
            }

            if ($group->is_required && $availableOptionIds === []) {
                throw ValidationException::withMessages([
                    'addon_option_ids' => "No available options for {$group->name}.",
                ]);
            }

            if ($group->selection_type === 'single' && count($selectedForGroup) > 1) {
                throw ValidationException::withMessages([
                    'addon_option_ids' => "Only one option can be selected for {$group->name}.",
                ]);
            }

            foreach ($selectedForGroup as $optionId) {
                $selectedByGroup[$group->id][] = $optionId;
            }
        }

        $snapshots = [];

        foreach ($menu->addonGroups as $group) {
            $groupSelections = $selectedByGroup[$group->id] ?? [];

            foreach ($groupSelections as $optionId) {
                /** @var MenuAddonOption|null $option */
                $option = $group->options->firstWhere('id', $optionId);

                if ($option === null || ! $option->is_available) {
                    continue;
                }

                $snapshots[] = [
                    'menu_addon_option_id' => $option->id,
                    'group_name' => $group->name,
                    'name' => $option->name,
                    'price' => $option->price,
                ];
            }
        }

        return $snapshots;
    }
}
