<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_page_requires_customer_session(): void
    {
        $response = $this->get(route('customer.menu.index'));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_menu_page_shows_available_and_sold_out_menus(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $category = Category::query()->create(['name' => 'Mains']);

        $availableMenu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
            'is_recommended' => true,
        ]);
        $availableMenu->categories()->attach($category);

        MenuModel::query()->create([
            'name' => 'Sold Out Dish',
            'price' => 25000,
            'is_available' => false,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->get(route('customer.menu.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/menu/index')
            ->where('serviceType', 'takeaway')
            ->has('menus', 2)
            ->where('menus.0.name', 'Chicken Rice')
            ->where('menus.0.is_available', true)
            ->where('menus.1.name', 'Sold Out Dish')
            ->where('menus.1.is_available', false)
            ->has('categories', 1)
            ->where('categories.0.name', 'Mains')
        );
    }

    public function test_menu_page_excludes_hardcoded_recommended_category(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $mains = Category::query()->create(['name' => 'Mains']);
        Category::query()->create(['name' => 'Recommended']);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
            'is_recommended' => true,
        ]);
        $menu->categories()->attach($mains);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->get(route('customer.menu.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/menu/index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Mains')
        );
    }
}
