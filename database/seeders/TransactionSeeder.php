<?php

namespace Database\Seeders;

use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Seed sample transaction data.
     */
    public function run(): void
    {
        $sundae = MenuModel::query()->where('name', 'Sundae')->with('addonGroups.options')->first();
        $coffee = MenuModel::query()->where('name', 'Iced Coffee')->with('addonGroups.options')->first();

        if ($sundae === null || $coffee === null) {
            return;
        }

        $sundaeMedium = $this->findOption($sundae, 'Size', 'Medium');
        $sundaeChoco = $this->findOption($sundae, 'Toppings', 'Choco sauce');
        $coffeeMedium = $this->findOption($coffee, 'Size', 'Medium');

        $paid = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '081234567890',
            'status' => 'paid',
            'total_bill' => 0,
        ]);

        $sundaeUnitPrice = $sundae->price + ($sundaeMedium?->price ?? 0) + ($sundaeChoco?->price ?? 0);

        TransactionItem::query()->create([
            'transaction_id' => $paid->id,
            'menu_id' => $sundae->id,
            'menu_name' => $sundae->name,
            'quantity' => 2,
            'unit_price' => $sundaeUnitPrice,
            'line_total' => $sundaeUnitPrice * 2,
            'addons' => array_values(array_filter([
                $sundaeMedium ? $this->addonSnapshot($sundaeMedium, 'Size') : null,
                $sundaeChoco ? $this->addonSnapshot($sundaeChoco, 'Toppings') : null,
            ])),
        ]);

        $paid->recalculateTotal();

        $inProgress = Transaction::query()->create([
            'customer_name' => 'Maria Santos',
            'customer_phone' => '089876543210',
            'status' => 'in_progress',
            'total_bill' => 0,
        ]);

        $coffeeUnitPrice = $coffee->price + ($coffeeMedium?->price ?? 0);

        TransactionItem::query()->create([
            'transaction_id' => $inProgress->id,
            'menu_id' => $coffee->id,
            'menu_name' => $coffee->name,
            'quantity' => 1,
            'unit_price' => $coffeeUnitPrice,
            'line_total' => $coffeeUnitPrice,
            'addons' => array_values(array_filter([
                $coffeeMedium ? $this->addonSnapshot($coffeeMedium, 'Size') : null,
            ])),
        ]);

        $inProgress->recalculateTotal();
    }

    private function findOption(MenuModel $menu, string $groupName, string $optionName): ?MenuAddonOption
    {
        foreach ($menu->addonGroups as $group) {
            if ($group->name !== $groupName) {
                continue;
            }

            foreach ($group->options as $option) {
                if ($option->name === $optionName) {
                    return $option;
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function addonSnapshot(MenuAddonOption $option, string $groupName): array
    {
        return [
            'menu_addon_option_id' => $option->id,
            'group_name' => $groupName,
            'name' => $option->name,
            'price' => $option->price,
        ];
    }
}
