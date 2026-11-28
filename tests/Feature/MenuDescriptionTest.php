<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MenuModel;
use App\Models\SalesChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_create_stores_multiline_description(): void
    {
        $description = "Crispy chicken\nwith sambal on the side.";

        $response = $this->postJson('/api/menu/create', [
            'name' => 'Chicken Rice',
            'description' => "  Crispy chicken\r\nwith sambal on the side.  ",
            'price' => 35000,
            'is_available' => true,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('menu.description', $description);

        $this->assertDatabaseHas('menus', [
            'name' => 'Chicken Rice',
            'description' => $description,
        ]);
    }

    public function test_api_update_changes_and_clears_description_without_wiping_it_when_omitted(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'description' => 'Original description',
            'price' => 35000,
            'is_available' => true,
        ]);

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ])->assertOk();

        $this->assertSame('Original description', $menu->fresh()->description);

        $updated = "Line one\nLine two";

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Chicken Rice',
            'description' => $updated,
            'price' => 36000,
            'is_available' => true,
        ])->assertOk()
            ->assertJsonPath('menu.description', $updated);

        $this->postJson("/api/menu/update/{$menu->id}", [
            'name' => 'Chicken Rice',
            'description' => "  \n  ",
            'price' => 36000,
            'is_available' => true,
        ])->assertOk()
            ->assertJsonPath('menu.description', null);

        $this->assertNull($menu->fresh()->description);
    }

    public function test_description_cannot_exceed_5000_characters(): void
    {
        $this->postJson('/api/menu/create', [
            'name' => 'Chicken Rice',
            'description' => str_repeat('a', 5001),
            'price' => 35000,
            'is_available' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('description');
    }

    public function test_admin_can_create_and_update_a_menu_description(): void
    {
        $description = "Slow cooked\nfor six hours.";

        $this->actingAsAdmin()
            ->post(route('admin.menus.store'), [
                'name' => 'Beef Bowl',
                'description' => $description,
                'price' => 48000,
                'is_available' => true,
                'is_recommended' => false,
                'addon_groups' => [],
            ])
            ->assertRedirect(route('admin.menus.index'));

        $menu = MenuModel::query()->where('name', 'Beef Bowl')->firstOrFail();
        $this->assertSame($description, $menu->description);

        $this->actingAsAdmin()
            ->put(route('admin.menus.update', $menu), [
                'name' => 'Beef Bowl',
                'description' => '',
                'price' => 48000,
                'is_available' => true,
                'is_recommended' => false,
                'addon_groups' => [],
            ])
            ->assertRedirect(route('admin.menus.show', $menu));

        $this->assertNull($menu->fresh()->description);
    }

    public function test_event_menu_create_stores_description(): void
    {
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => 'Bazaar',
            'starts_at' => '2026-09-26',
            'ends_at' => '2026-09-26',
            'closes_store' => false,
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.events.menus.store', $event), [
                'name' => 'Event Roll',
                'description' => "Festival only\nwhile supplies last.",
                'price' => 28000,
                'is_available' => true,
                'is_recommended' => false,
                'addon_groups' => [],
            ])
            ->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('menus', [
            'name' => 'Event Roll',
            'description' => "Festival only\nwhile supplies last.",
        ]);
    }

    public function test_public_and_customer_pages_include_the_description(): void
    {
        $this->withoutVite();

        $description = "Crispy chicken\nwith sambal.";

        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'description' => $description,
            'price' => 35000,
            'is_available' => true,
        ]);

        $this->get(route('menu.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('menu/index')
                ->where('menus.0.description', $description)
            );

        $this->get(route('menu.show', $menu))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('menu/show')
                ->where('menu.description', $description)
            );

        $customer = Customer::query()->create([
            'name' => 'Alex Tan',
            'phone' => '6281234567890',
        ]);

        $this->withSession(['customer_id' => $customer->id, 'service_type' => 'takeaway'])
            ->get(route('customer.menu.show', $menu))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('customer/menu/show')
                ->where('menu.description', $description)
            );
    }
}
