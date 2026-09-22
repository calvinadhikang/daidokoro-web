<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use App\Models\OperatingHour;
use App\Models\SalesChannel;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OperatingHour::ensureWeekExists();
        Carbon::setTestNow(Carbon::parse('2026-09-23 10:00:00', StoreHoursService::TIMEZONE));
    }

    public function test_events_index_renders(): void
    {
        $this->get(route('admin.events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/index')
                ->has('channels')
            );
    }

    public function test_admin_can_create_event_and_assign_store_menu(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Chicken Rice',
            'price' => 35000,
            'is_available' => true,
        ]);

        $create = $this->post(route('admin.events.store'), [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-24',
        ]);

        $event = SalesChannel::query()->events()->firstOrFail();
        $create->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('operating_closures', [
            'sales_channel_id' => $event->id,
            'label' => 'Bazaar Senayan',
        ]);

        $this->post(route('admin.events.menus.assign', $event), [
            'menus' => [
                ['menu_id' => $menu->id, 'price_override' => 40000],
            ],
        ])->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $event->id,
            'menu_id' => $menu->id,
            'price_override' => 40000,
        ]);
    }

    public function test_overlapping_events_are_rejected(): void
    {
        $this->post(route('admin.events.store'), [
            'name' => 'Bazaar A',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->assertRedirect();

        $this->from(route('admin.events.create'))
            ->post(route('admin.events.store'), [
                'name' => 'Bazaar B',
                'starts_at' => '2026-09-24',
                'ends_at' => '2026-09-26',
            ])
            ->assertRedirect(route('admin.events.create'))
            ->assertSessionHasErrors();
    }
}
