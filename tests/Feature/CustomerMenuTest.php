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

    public function test_menu_page_shows_available_menus_and_categories(): void
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
            ->has('menus', 1)
            ->where('menus.0.name', 'Chicken Rice')
            ->has('categories', 1)
            ->where('categories.0.name', 'Mains')
        );
    }
}
