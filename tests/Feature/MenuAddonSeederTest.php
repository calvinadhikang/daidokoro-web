<?php

namespace Tests\Feature;

use App\Models\MenuAddonGroup;
use App\Models\MenuModel;
use Database\Seeders\MenuAddonSeeder;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MenuAddonSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_copies_addon_groups_onto_matching_menus(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuAddonSeeder::class);

        $takoyaki8 = MenuModel::query()->where('name', 'Takoyaki 8pc')->firstOrFail();
        $takoyaki15 = MenuModel::query()->where('name', 'Takoyaki 15pc')->firstOrFail();

        $this->assertSame(
            ['Pilihan Mayo', 'Tambahan Mozarella'],
            $takoyaki8->addonGroups()->pluck('name')->all(),
        );
        $this->assertSame(
            ['Pilihan Mayo', 'Tambahan Mozarella'],
            $takoyaki15->addonGroups()->pluck('name')->all(),
        );

        $mayo8 = $takoyaki8->addonGroups()->where('name', 'Pilihan Mayo')->firstOrFail();
        $mayo15 = $takoyaki15->addonGroups()->where('name', 'Pilihan Mayo')->firstOrFail();

        $this->assertNotSame($mayo8->id, $mayo15->id);
        $this->assertTrue($mayo8->is_required);
        $this->assertSame('single', $mayo8->selection_type);
        $this->assertSame(
            ['Original', 'Cheese', 'Mentai', 'Spicy Mayo'],
            $mayo8->options()->pluck('name')->all(),
        );
        $this->assertSame([0, 0, 0, 0], $mayo8->options()->pluck('price')->all());

        $mozarella = $takoyaki8->addonGroups()->where('name', 'Tambahan Mozarella')->firstOrFail();
        $this->assertFalse($mozarella->is_required);
        $this->assertSame(['Mozarella'], $mozarella->options()->pluck('name')->all());
        $this->assertSame([10000], $mozarella->options()->pluck('price')->all());
    }

    public function test_ramen_menus_include_gram_choice_and_shared_noodle_groups(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuAddonSeeder::class);

        $ramenChicken = MenuModel::query()->where('name', 'Ramen Chicken')->firstOrFail();
        $this->assertSame(
            [
                'Pilihan Chicken Noodle',
                'Pilihan Mie Ramen',
                'Pilih Kuah Noodle',
                'Tambahan Protein Noodle',
                'Spicy Level',
            ],
            $ramenChicken->addonGroups()->pluck('name')->all(),
        );

        $chickenNoodle = $ramenChicken->addonGroups()->where('name', 'Pilihan Chicken Noodle')->firstOrFail();
        $this->assertSame(['Chicken Katsu', 'Chicken Grill'], $chickenNoodle->options()->pluck('name')->all());
        $this->assertSame([0, 2000], $chickenNoodle->options()->pluck('price')->all());

        $ramenBeefMenus = MenuModel::query()->where('name', 'Ramen Beef')->get();
        $this->assertGreaterThanOrEqual(2, $ramenBeefMenus->count());

        foreach ($ramenBeefMenus as $ramenBeef) {
            $this->assertSame(
                [
                    'Pilihan Mie Ramen',
                    'Pilih Kuah Noodle',
                    'Tambahan Protein Noodle',
                    'Spicy Level',
                ],
                $ramenBeef->addonGroups()->pluck('name')->all(),
            );
        }
    }

    public function test_rice_bowl_curry_does_not_get_rice_bowl_sauce_group(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuAddonSeeder::class);

        $curry = MenuModel::query()->where('name', 'Rice Bowl Curry')->firstOrFail();
        $beef = MenuModel::query()->where('name', 'Rice Bowl Beef')->firstOrFail();

        $this->assertSame(
            ['Pilihan Rice Bowl Curry', 'Tambahan Rice Bowl', 'Spicy Level'],
            $curry->addonGroups()->pluck('name')->all(),
        );
        $this->assertSame(
            ['Tambahan Rice Bowl', 'Pilihan Sauce Rice Bowl', 'Spicy Level'],
            $beef->addonGroups()->pluck('name')->all(),
        );

        $extras = $curry->addonGroups()->where('name', 'Tambahan Rice Bowl')->firstOrFail();
        $this->assertFalse($extras->is_required);
        $this->assertSame('multiple', $extras->selection_type);
        $this->assertSame(
            ['Scramble Egg', 'Mozarella', 'Tambah Beef'],
            $extras->options()->pluck('name')->all(),
        );
        $this->assertSame([7000, 10000, 20000], $extras->options()->pluck('price')->all());
    }

    public function test_spider_roll_and_duplicate_hana_roll_receive_addons(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuAddonSeeder::class);

        $spider = MenuModel::query()->where('name', 'Spider Roll (5 Pcs)')->firstOrFail();
        $this->assertSame(
            ['Tambahan Mozarella', 'Spicy Level'],
            $spider->addonGroups()->pluck('name')->all(),
        );

        $hanaRolls = MenuModel::query()->where('name', 'Hana Roll')->get();
        $this->assertGreaterThanOrEqual(2, $hanaRolls->count());

        foreach ($hanaRolls as $hanaRoll) {
            $this->assertSame(
                ['Pilih Ikan Fresh', 'Tambahan Mozarella', 'Spicy Level'],
                $hanaRoll->addonGroups()->pluck('name')->all(),
            );
        }
    }

    public function test_rerunning_seeder_replaces_groups_instead_of_duplicating(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuAddonSeeder::class);
        $this->seed(MenuAddonSeeder::class);

        $takoyaki = MenuModel::query()->where('name', 'Takoyaki 8pc')->firstOrFail();

        $this->assertSame(2, $takoyaki->addonGroups()->count());
        $this->assertSame(1, MenuAddonGroup::query()->where('name', 'Dingin / Tidak Dingin')->count());
    }

    public function test_seeder_fails_when_a_product_name_is_missing(): void
    {
        MenuModel::query()->create([
            'name' => 'Takoyaki 8pc',
            'price' => 40000,
            'is_available' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Menu add-on catalog references unknown menu names: Menu Yang Tidak Ada');

        (new MenuAddonSeeder)->seedFrom([
            [
                'name' => 'Pilihan Mayo',
                'is_required' => true,
                'selection_type' => 'single',
                'options' => [
                    ['name' => 'Original', 'price' => 0],
                ],
                'products' => [
                    'Takoyaki 8pc',
                    'Menu Yang Tidak Ada',
                ],
            ],
        ]);
    }
}
