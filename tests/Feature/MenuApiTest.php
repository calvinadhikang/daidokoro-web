<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_menus_with_categories(): void
    {
        $category = Category::query()->create(['name' => 'Sushi']);

        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
            'is_recommended' => true,
        ]);
        $menu->categories()->attach($category);

        $response = $this->getJson('/api/menu');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $menu->id,
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
            'categories' => [
                ['id' => $category->id, 'name' => 'Sushi'],
            ],
        ]);
    }

    public function test_categories_endpoint_returns_all_categories(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        $drinks = Category::query()->create(['name' => 'Drinks']);

        $response = $this->getJson('/api/menu/categories');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['id' => $sushi->id, 'name' => 'Sushi']);
        $response->assertJsonFragment(['id' => $drinks->id, 'name' => 'Drinks']);
    }

    public function test_detail_returns_menu_with_addon_groups(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Iced Coffee',
            'price' => 25000,
            'is_available' => true,
        ]);

        $group = MenuAddonGroup::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Size',
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        MenuAddonOption::query()->create([
            'menu_addon_group_id' => $group->id,
            'name' => 'Large',
            'price' => 5000,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $response = $this->getJson("/api/menu/detail/{$menu->id}");

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $menu->id,
            'name' => 'Iced Coffee',
        ]);
        $response->assertJsonStructure([
            'addon_groups' => [
                ['id', 'name', 'selection_type', 'options'],
            ],
        ]);
    }

    public function test_create_menu_returns_formatted_menu(): void
    {
        $response = $this->postJson('/api/menu/create', [
            'name' => 'Matcha Latte',
            'price' => 32000,
            'is_available' => true,
        ]);

        $response->assertCreated();
        $response->assertJson([
            'success' => true,
            'menu' => [
                'name' => 'Matcha Latte',
                'price' => 32000,
                'is_available' => true,
            ],
        ]);

        $this->assertDatabaseHas('menus', [
            'name' => 'Matcha Latte',
            'price' => 32000,
        ]);
    }

    public function test_create_menu_syncs_categories(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        $drinks = Category::query()->create(['name' => 'Drinks']);

        $response = $this->postJson('/api/menu/create', [
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
            'category_ids' => [$sushi->id, $drinks->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('menu.categories.0.name', 'Drinks');
        $response->assertJsonPath('menu.categories.1.name', 'Sushi');

        $menu = MenuModel::query()->where('name', 'Salmon Roll')->firstOrFail();
        $this->assertSame([$drinks->id, $sushi->id], $menu->categories()->pluck('categories.id')->all());
    }

    public function test_create_menu_with_addon_groups(): void
    {
        $response = $this->postJson('/api/menu/create', [
            'name' => 'Iced Coffee',
            'price' => 25000,
            'is_available' => true,
            'addon_groups' => [
                [
                    'name' => 'Size',
                    'selection_type' => 'single',
                    'is_required' => true,
                    'options' => [
                        ['name' => 'Regular', 'price' => 0, 'is_available' => true],
                        ['name' => 'Large', 'price' => 5000, 'is_available' => true],
                    ],
                ],
                [
                    'name' => 'Toppings',
                    'selection_type' => 'multiple',
                    'is_required' => false,
                    'options' => [
                        ['name' => 'Pearl', 'price' => 3000, 'is_available' => true],
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('menu.addon_groups.0.name', 'Size');
        $response->assertJsonPath('menu.addon_groups.0.selection_type', 'single');
        $response->assertJsonPath('menu.addon_groups.0.is_required', true);
        $response->assertJsonPath('menu.addon_groups.1.name', 'Toppings');
        $response->assertJsonPath('menu.addon_groups.1.selection_type', 'multiple');

        $menu = MenuModel::query()->where('name', 'Iced Coffee')->firstOrFail();
        $this->assertSame(2, $menu->addonGroups()->count());
    }

    public function test_update_menu_changes_fields(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Old Name',
            'price' => 10000,
            'is_available' => true,
        ]);

        $response = $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'New Name',
            'price' => 15000,
            'is_available' => false,
            'is_recommended' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'menu' => [
                'name' => 'New Name',
                'price' => 15000,
                'is_available' => false,
                'is_recommended' => true,
            ],
        ]);
    }

    public function test_delete_menu_removes_record(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'To Delete',
            'price' => 10000,
            'is_available' => true,
        ]);

        $response = $this->postJson("/api/menu/delete/{$menu->id}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    }
}
