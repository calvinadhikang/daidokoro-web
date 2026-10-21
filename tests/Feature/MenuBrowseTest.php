<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuAddonGroup;
use App\Models\MenuAddonOption;
use App\Models\MenuModel;
use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use App\Models\SalesChannel;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            ->has('storeStatus')
        );
    }

    public function test_public_menu_page_excludes_hardcoded_recommended_category(): void
    {
        $mains = Category::query()->create(['name' => 'Mains']);
        Category::query()->create(['name' => 'Recommended']);

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
            'is_recommended' => true,
        ]);
        $menu->categories()->attach($mains);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/index')
            ->has('categories', 1)
            ->where('categories.0.name', 'Mains')
        );
    }

    public function test_public_menu_shows_store_catalog_while_an_event_closes_the_store(): void
    {
        OperatingHour::ensureWeekExists();
        Carbon::setTestNow(Carbon::parse('2026-09-26 12:00:00', StoreHoursService::TIMEZONE));

        $storeMenu = MenuModel::query()->create([
            'name' => 'Store Salmon',
            'price' => 45000,
            'is_available' => true,
        ]);

        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Bazaar',
            'starts_at' => '2026-09-26',
            'ends_at' => '2026-09-26',
            'closes_store' => true,
        ]);

        $eventMenu = new MenuModel([
            'name' => 'Event Only Roll',
            'price' => 30000,
            'is_available' => true,
        ]);
        $eventMenu->assignToSalesChannelId = $event->id;
        $eventMenu->save();

        OperatingClosure::query()->create([
            'sales_channel_id' => $event->id,
            'starts_at' => '2026-09-26',
            'ends_at' => '2026-09-26',
            'label' => 'Bazaar',
        ]);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/index')
            ->where('storeStatus.is_open', false)
            ->has('menus', 1)
            ->where('menus.0.id', $storeMenu->id)
            ->where('menus.0.name', 'Store Salmon')
        );

        Carbon::setTestNow();
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

    public function test_public_menu_detail_shows_addons_without_a_cart_action(): void
    {
        $category = Category::query()->create(['name' => 'Mains']);
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);
        $menu->categories()->attach($category);

        $group = MenuAddonGroup::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Spice Level',
            'selection_type' => 'single',
            'is_required' => false,
            'sort_order' => 0,
        ]);

        MenuAddonOption::query()->create([
            'menu_addon_group_id' => $group->id,
            'name' => 'Medium',
            'price' => 0,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $response = $this->get(route('menu.show', $menu));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/show')
            ->where('menu.name', 'Chicken Rice')
            ->where('menu.addon_groups.0.name', 'Spice Level')
            ->where('menu.addon_groups.0.options.0.name', 'Medium')
            ->where('orderUrl', '/customer/login?redirect='.rawurlencode('/customer/menu/'.$menu->id))
            ->missing('cart')
        );
    }

    public function test_public_menu_detail_sends_logged_in_customers_to_the_order_page(): void
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
            ->withSession(['customer_id' => $customer->id])
            ->get(route('menu.show', $menu));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/show')
            ->where('orderUrl', '/customer/menu/'.$menu->id)
        );
    }

    public function test_public_menu_detail_tells_guests_when_the_store_opens(): void
    {
        OperatingHour::ensureWeekExists();
        Carbon::setTestNow(Carbon::parse('2026-06-27 13:00:00', StoreHoursService::TIMEZONE));

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $response = $this->get(route('menu.show', $menu));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('menu/show')
            ->where('storeStatus.is_open', false)
            ->where('nextOpening.message', 'Buka lagi hari ini pukul 15.00 WIB')
        );

        Carbon::setTestNow();
    }

    public function test_public_menu_detail_hides_event_only_menus(): void
    {
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Bazaar',
            'starts_at' => '2026-09-26',
            'ends_at' => '2026-09-26',
            'closes_store' => true,
        ]);

        $eventMenu = new MenuModel([
            'name' => 'Event Only Roll',
            'price' => 30000,
            'is_available' => true,
        ]);
        $eventMenu->assignToSalesChannelId = $event->id;
        $eventMenu->save();

        $this->get(route('menu.show', $eventMenu))->assertNotFound();
    }
}
