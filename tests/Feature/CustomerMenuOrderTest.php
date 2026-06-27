<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMenuOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_page_requires_customer_session(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $response = $this->get(route('customer.menu.show', $menu));

        $response->assertRedirect(route('customer.login'));
    }

    public function test_order_page_renders_for_available_menu(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->get(route('customer.menu.show', $menu));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/menu/show')
            ->where('menu.name', 'Chicken Rice')
        );
    }

    public function test_unavailable_menu_returns_not_found(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Sold Out Dish',
            'price' => 25000,
            'is_available' => false,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id])
            ->get(route('customer.menu.show', $menu));

        $response->assertNotFound();
    }

    public function test_customer_can_add_menu_with_addons_to_cart(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $group = MenuAddonGroup::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Spice Level',
            'selection_type' => 'single',
            'is_required' => true,
            'sort_order' => 0,
        ]);

        $option = MenuAddonOption::query()->create([
            'menu_addon_group_id' => $group->id,
            'name' => 'Medium',
            'price' => 0,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->post(route('customer.menu.store', $menu), [
                'quantity' => 2,
                'addon_option_ids' => [$option->id],
            ]);

        $response->assertRedirect(route('customer.menu.index'));
        $response->assertSessionHas('success');

        $cart = session('customer_cart');
        $this->assertIsArray($cart);
        $this->assertCount(1, $cart);
        $this->assertSame('Chicken Rice', $cart[0]['menu_name']);
        $this->assertSame(2, $cart[0]['quantity']);
        $this->assertSame(70000, $cart[0]['line_total']);
    }
}
