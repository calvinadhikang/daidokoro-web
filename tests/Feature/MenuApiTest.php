<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\SalesChannel;
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

    public function test_categories_endpoint_excludes_hardcoded_recommended_category(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        Category::query()->create(['name' => 'Recommended']);

        $response = $this->getJson('/api/menu/categories');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['id' => $sushi->id, 'name' => 'Sushi']);
        $response->assertJsonMissing(['name' => 'Recommended']);
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

    public function test_toggle_availability_flips_is_available(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Nigiri',
            'price' => 25000,
            'is_available' => true,
        ]);

        $response = $this->postJson("/api/menu/toggle-availability/{$menu->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'menu' => [
                'id' => $menu->id,
                'name' => 'Salmon Nigiri',
                'is_available' => false,
            ],
        ]);
        $this->assertTrue($menu->fresh()->is_available);
        $this->assertFalse((bool) $menu->salesChannels()->first()?->pivot?->is_available);

        $response = $this->postJson("/api/menu/toggle-availability/{$menu->id}");

        $response->assertOk();
        $response->assertJsonPath('menu.is_available', true);
        $this->assertTrue((bool) $menu->salesChannels()->first()?->pivot?->is_available);
    }

    public function test_create_category(): void
    {
        $response = $this->postJson('/api/menu/categories/create', [
            'name' => 'Sushi',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('category.name', 'Sushi');
        $response->assertJsonPath('category.menus_count', 0);
        $this->assertDatabaseHas('categories', ['name' => 'Sushi']);
    }

    public function test_update_category_renames_without_removing_menus(): void
    {
        $category = Category::query()->create(['name' => 'Sushi']);
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $menu->categories()->attach($category);

        $response = $this->postJson("/api/menu/categories/update/{$category->id}", [
            'name' => 'Nigiri',
        ]);

        $response->assertOk();
        $response->assertJsonPath('category.name', 'Nigiri');
        $response->assertJsonPath('category.menus_count', 1);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nigiri']);
        $this->assertTrue($menu->fresh()->categories()->whereKey($category->id)->exists());
    }

    public function test_delete_category_does_not_delete_menus(): void
    {
        $category = Category::query()->create(['name' => 'Sushi']);
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $menu->categories()->attach($category);

        $response = $this->postJson("/api/menu/categories/delete/{$category->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('menus', ['id' => $menu->id]);
        $this->assertSame(0, $menu->fresh()->categories()->count());
    }

    public function test_create_rejects_hardcoded_recommended_category(): void
    {
        $response = $this->postJson('/api/menu/categories/create', [
            'name' => 'Recommended',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertDatabaseMissing('categories', ['name' => 'Recommended']);
    }

    public function test_category_detail_lists_every_menu_with_assignment(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        $drinks = Category::query()->create(['name' => 'Drinks']);
        $assigned = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $other = MenuModel::query()->create([
            'name' => 'Green Tea',
            'price' => 15000,
            'is_available' => false,
        ]);
        $assigned->categories()->attach([$sushi->id, $drinks->id]);
        $other->categories()->attach($drinks);

        $response = $this->getJson("/api/menu/categories/detail/{$sushi->id}");

        $response->assertOk();
        $response->assertJsonPath('category.name', 'Sushi');
        $response->assertJsonPath('category.menus_count', 1);
        $response->assertJsonFragment([
            'id' => $assigned->id,
            'name' => 'Salmon Roll',
            'assigned' => true,
        ]);
        $response->assertJsonFragment([
            'id' => $other->id,
            'name' => 'Green Tea',
            'assigned' => false,
            'is_available' => false,
        ]);
    }

    public function test_assign_and_unassign_category_menu_keeps_other_categories(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        $drinks = Category::query()->create(['name' => 'Drinks']);
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $menu->categories()->attach($drinks);

        $assign = $this->postJson("/api/menu/categories/menus/assign/{$sushi->id}", [
            'menu_id' => $menu->id,
        ]);

        $assign->assertOk();
        $assign->assertJsonPath('assigned', true);
        $assign->assertJsonPath('category.menus_count', 1);
        $this->assertTrue($menu->fresh()->categories()->whereKey($sushi->id)->exists());
        $this->assertTrue($menu->fresh()->categories()->whereKey($drinks->id)->exists());

        $unassign = $this->postJson("/api/menu/categories/menus/unassign/{$sushi->id}", [
            'menu_id' => $menu->id,
        ]);

        $unassign->assertOk();
        $unassign->assertJsonPath('assigned', false);
        $unassign->assertJsonPath('category.menus_count', 0);
        $this->assertFalse($menu->fresh()->categories()->whereKey($sushi->id)->exists());
        $this->assertTrue($menu->fresh()->categories()->whereKey($drinks->id)->exists());
    }

    public function test_categories_list_includes_menus_count(): void
    {
        $sushi = Category::query()->create(['name' => 'Sushi']);
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Roll',
            'price' => 45000,
            'is_available' => true,
        ]);
        $menu->categories()->attach($sushi);

        $response = $this->getJson('/api/menu/categories');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $sushi->id,
            'name' => 'Sushi',
            'menus_count' => 1,
        ]);
    }

    public function test_detail_includes_sales_channel_ids(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Miso Soup',
            'price' => 18000,
            'is_available' => true,
        ]);

        $this->getJson("/api/menu/detail/{$menu->id}")
            ->assertOk()
            ->assertJsonPath('sales_channel_ids', [SalesChannel::store()->id]);
    }

    public function test_create_with_sales_channel_ids_assigns_only_those_channels(): void
    {
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Popup',
            'starts_at' => '2026-11-01',
            'ends_at' => '2026-11-02',
            'closes_store' => true,
        ]);

        $response = $this->postJson('/api/menu/create', [
            'name' => 'Event Bowl',
            'price' => 40000,
            'is_available' => true,
            'sales_channel_ids' => [$event->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('menu.sales_channel_ids', [$event->id]);

        $menu = MenuModel::query()->where('name', 'Event Bowl')->firstOrFail();
        $this->assertSame(
            [$event->id],
            $menu->salesChannels()->pluck('sales_channels.id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function test_update_keeps_channels_unless_sales_channel_ids_are_sent(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 15000,
            'is_available' => true,
        ]);
        $storeId = SalesChannel::store()->id;
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Popup',
            'starts_at' => '2026-11-01',
            'ends_at' => '2026-11-02',
            'closes_store' => true,
        ]);

        $this->postJson("/api/channels/{$event->id}/menus/assign", [
            'menus' => [
                ['menu_id' => $menu->id, 'price_override' => 22000],
            ],
        ])->assertOk();

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Edamame',
            'price' => 16000,
            'is_available' => true,
        ])->assertOk();

        $menu->refresh();
        $this->assertEqualsCanonicalizing(
            [$storeId, $event->id],
            $menu->salesChannels()->pluck('sales_channels.id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(
            22000,
            (int) $menu->salesChannels()->where('sales_channels.id', $event->id)->firstOrFail()->pivot->price_override,
        );

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Edamame',
            'price' => 16000,
            'is_available' => true,
            'sales_channel_ids' => [$storeId],
        ])->assertOk()
            ->assertJsonPath('menu.sales_channel_ids', [$storeId]);

        $this->assertSame(
            [$storeId],
            $menu->fresh()->salesChannels()->pluck('sales_channels.id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function test_empty_sales_channel_ids_fall_back_to_the_store(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Gyoza',
            'price' => 28000,
            'is_available' => true,
        ]);
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Popup',
            'starts_at' => '2026-11-01',
            'ends_at' => '2026-11-02',
            'closes_store' => true,
        ]);
        $menu->salesChannels()->sync([
            $event->id => ['price_override' => null, 'is_available' => true],
        ]);

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Gyoza',
            'price' => 28000,
            'is_available' => true,
            'sales_channel_ids' => [],
        ])->assertOk()
            ->assertJsonPath('menu.sales_channel_ids', [SalesChannel::store()->id]);
    }
}
