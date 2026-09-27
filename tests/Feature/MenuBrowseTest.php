<?php

namespace Tests\Feature;

use App\Models\Category;
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
}
