<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('admin.login'));

        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/login'));
    }

    public function test_superadmin_can_sign_in_and_reach_admin_pages(): void
    {
        $this->post(route('admin.login.store'), [
            'username' => 'daidokoro',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();

        $this->post(route('admin.login.store'), [
            'username' => 'daidokoro',
            'password' => 'daidokoro',
        ])->assertRedirect(route('admin.menus.index'));

        $this->assertAuthenticated();

        $this->get(route('admin.menus.index'))->assertOk();
    }

    public function test_logout_returns_to_the_login_page(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->get(route('admin.menus.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_api_stays_open_without_an_admin_session(): void
    {
        MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $this->getJson('/api/menu')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Chicken Rice']);
    }
}
