<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuBrowseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_menu_page_does_not_require_login(): void
    {
        MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

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
        );
    }
}
