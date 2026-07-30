<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_menu_page_does_not_require_login(): void
    {
        $category = Category::query()->create(['name' => 'Mains']);

        $availableMenu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);
        $availableMenu->categories()->attach($category);

        MenuModel::query()->create([
            'name' => 'Sold Out Dish',
            'price' => 25000,
            'is_available' => false,
        ]);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/index')
            ->has('menus', 2)
            ->where('menus.0.name', 'Chicken Rice')
            ->where('menus.1.name', 'Sold Out Dish')
            ->has('categories', 1)
            ->where('categories.0.name', 'Mains')
        );
    }

    public function test_menu_availability_can_be_toggled_from_public_menu_page(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $response = $this->patch(route('menu.availability.toggle', $menu));

        $response->assertRedirect(route('menu.index'));
        $this->assertFalse($menu->fresh()->is_available);

        $response = $this->patch(route('menu.availability.toggle', $menu));

        $response->assertRedirect(route('menu.index'));
        $this->assertTrue($menu->fresh()->is_available);
    }
}
