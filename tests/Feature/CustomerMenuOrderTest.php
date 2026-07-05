<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\Transaction;
use App\Models\TransactionItem;
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

    public function test_adding_menu_puts_item_in_cart_not_transaction(): void
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
            ->post(route('customer.menu.store', $menu), [
                'quantity' => 2,
            ]);

        $response->assertRedirect(route('customer.cart.index'));
        $this->assertDatabaseCount('transaction_items', 0);

        $cart = session('customer_cart');
        $this->assertIsArray($cart);
        $this->assertCount(1, $cart);
        $this->assertSame('Chicken Rice', $cart[0]['menu_name']);
        $this->assertSame(2, $cart[0]['quantity']);
        $this->assertSame(70000, $cart[0]['line_total']);
    }

    public function test_checkout_creates_transaction_from_cart(): void
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

        $session = ['customer_id' => $customer->id, 'service_type' => 'takeaway'];

        $this
            ->withSession($session)
            ->post(route('customer.menu.store', $menu), ['quantity' => 2]);

        $response = $this
            ->withSession(array_merge($session, ['customer_cart' => session('customer_cart')]))
            ->post(route('customer.cart.checkout'));

        $response->assertRedirect(route('customer.order.index'));
        $this->assertDatabaseHas('transactions', [
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'service_type' => 'takeaway',
            'status' => 'in_progress',
            'total_bill' => 70000,
        ]);

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertSame($transaction->id, session('transaction_id'));
        $this->assertSame([], session('customer_cart') ?? []);
    }

    public function test_checkout_adds_cart_items_to_existing_transaction(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $firstMenu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $secondMenu = MenuModel::query()->create([
            'name' => 'Iced Tea',
            'price' => 15000,
            'is_available' => true,
        ]);

        $session = ['customer_id' => $customer->id, 'service_type' => 'dine_in'];

        $this->withSession($session)->post(route('customer.menu.store', $firstMenu), ['quantity' => 1]);
        $this
            ->withSession(array_merge($session, ['customer_cart' => session('customer_cart')]))
            ->post(route('customer.cart.checkout'));

        $this->withSession($session)->post(route('customer.menu.store', $secondMenu), ['quantity' => 2]);
        $this
            ->withSession(array_merge($session, ['customer_cart' => session('customer_cart')]))
            ->post(route('customer.cart.checkout'));

        $this->assertDatabaseCount('transactions', 1);

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(65000, $transaction->total_bill);
        $this->assertDatabaseCount('transaction_items', 2);
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

        $response->assertRedirect(route('customer.cart.index'));

        $cart = session('customer_cart');
        $this->assertIsArray($cart);
        $this->assertSame('Chicken Rice', $cart[0]['menu_name']);
        $this->assertSame(2, $cart[0]['quantity']);
        $this->assertSame(70000, $cart[0]['line_total']);
    }

    public function test_checkout_starts_new_transaction_after_previous_is_paid(): void
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

        Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'service_type' => 'dine_in',
            'status' => 'paid',
            'total_bill' => 35000,
        ]);

        $session = ['customer_id' => $customer->id, 'service_type' => 'dine_in'];

        $this->withSession($session)->post(route('customer.menu.store', $menu), ['quantity' => 1]);
        $this
            ->withSession(array_merge($session, ['customer_cart' => session('customer_cart')]))
            ->post(route('customer.cart.checkout'));

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseHas('transactions', [
            'customer_phone' => '6281234567890',
            'status' => 'in_progress',
            'total_bill' => 35000,
        ]);
    }

    public function test_checkout_rejects_empty_cart(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id])
            ->post(route('customer.cart.checkout'));

        $response->assertSessionHasErrors(['cart']);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_checkout_rejects_unavailable_menu(): void
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

        $session = ['customer_id' => $customer->id, 'service_type' => 'dine_in'];

        $this
            ->withSession($session)
            ->post(route('customer.menu.store', $menu), ['quantity' => 1]);

        $menu->update(['is_available' => false]);

        $response = $this
            ->withSession(array_merge($session, ['customer_cart' => session('customer_cart')]))
            ->post(route('customer.cart.checkout'));

        $response->assertSessionHasErrors(['cart']);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertCount(1, session('customer_cart'));
    }

    public function test_login_resumes_in_progress_transaction_by_phone(): void
    {
        Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'service_type' => 'takeaway',
            'status' => 'in_progress',
            'total_bill' => 50000,
        ]);

        $response = $this->post(route('customer.login.store'), [
            'name' => 'Alex Tan',
            'phone' => '081234567890',
            'service_type' => 'takeaway',
        ]);

        $response->assertRedirect(route('customer.menu.index'));
        $this->assertSame($transaction->id, session('transaction_id'));
    }

    public function test_cart_page_shows_cart_items(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $cartItem = [
            'menu_id' => 1,
            'menu_name' => 'Iced Tea',
            'quantity' => 1,
            'unit_price' => 15000,
            'line_total' => 15000,
            'addon_option_ids' => [],
            'addons' => [],
        ];

        $response = $this
            ->withSession([
                'customer_id' => $customer->id,
                'service_type' => 'dine_in',
                'customer_cart' => [$cartItem],
            ])
            ->get(route('customer.cart.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/cart/index')
            ->where('cartTotal', 15000)
            ->has('cart', 1)
        );
    }

    public function test_order_page_shows_active_transaction(): void
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

        $transaction = Transaction::query()->create([
            'customer_name' => 'Alex Tan',
            'customer_phone' => '6281234567890',
            'service_type' => 'dine_in',
            'status' => 'in_progress',
            'total_bill' => 35000,
        ]);

        TransactionItem::query()->create([
            'transaction_id' => $transaction->id,
            'menu_id' => $menu->id,
            'menu_name' => 'Chicken Rice',
            'quantity' => 1,
            'unit_price' => 35000,
            'line_total' => 35000,
            'addons' => null,
        ]);

        $response = $this
            ->withSession(['customer_id' => $customer->id, 'service_type' => 'dine_in'])
            ->get(route('customer.order.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/order/index')
            ->where('transaction.id', $transaction->id)
            ->has('itemGroups', 1)
        );
    }
}
