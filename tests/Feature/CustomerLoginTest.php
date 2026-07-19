<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $response = $this->get(route('customer.login', [
            'service_type' => 'takeaway',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('customer/login')
            ->missing('phonePrefix')
            ->where('serviceType', 'takeaway')
            ->where('tableCode', null)
        );
    }

    public function test_customer_is_created_on_login(): void
    {
        $response = $this->post(route('customer.login.store'), [
            'name' => 'Alex Tan',
            'phone' => '081234567890',
            'service_type' => 'takeaway',
        ]);

        $response->assertRedirect(route('customer.menu.index'));
        $response->assertSessionHas('success', 'Welcome, Alex Tan!');

        $this->assertDatabaseHas('customers', [
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $customer = Customer::query()->first();
        $this->assertNotNull($customer);
        $this->assertSame($customer->id, session('customer_id'));
        $this->assertSame('takeaway', session('service_type'));
    }

    public function test_existing_customer_is_updated_by_phone(): void
    {
        Customer::query()->create([
            'name' => 'Old Name',
            'phone' => '6281234567890',
        ]);

        $this->post(route('customer.login.store'), [
            'name' => 'Alex Tan',
            'phone' => '81234567890',
            'service_type' => 'dine_in',
        ]);

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);
    }

    public function test_login_requires_name_and_phone(): void
    {
        $response = $this->post(route('customer.login.store'), []);

        $response->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_login_rejects_invalid_phone(): void
    {
        $response = $this->post(route('customer.login.store'), [
            'name' => 'Alex Tan',
            'phone' => '12345',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_logout_clears_customer_session_and_redirects_home(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $response = $this->withSession([
            'customer_id' => $customer->id,
            'service_type' => 'dine_in',
            'table_code' => 'A1',
            'customer_cart' => [['menu_id' => 1]],
            'transaction_id' => 99,
        ])->post(route('customer.logout'));

        $response->assertRedirect(route('home'));
        $this->assertFalse(session()->has('customer_id'));
        $this->assertFalse(session()->has('service_type'));
        $this->assertFalse(session()->has('table_code'));
        $this->assertFalse(session()->has('customer_cart'));
        $this->assertFalse(session()->has('transaction_id'));
    }

    public function test_logout_requires_customer_session(): void
    {
        $response = $this->post(route('customer.logout'));

        $response->assertRedirect(route('customer.login'));
    }
}
