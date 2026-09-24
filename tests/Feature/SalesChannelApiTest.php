<?php

namespace Tests\Feature;

use App\Models\MenuModel;
use App\Models\OperatingClosure;
use App\Models\OperatingHour;
use App\Models\SalesChannel;
use App\Models\Transaction;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalesChannelApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        OperatingHour::ensureWeekExists();
        Carbon::setTestNow(Carbon::parse('2026-09-23 10:00:00', StoreHoursService::TIMEZONE));
    }

    public function test_store_channel_is_seeded_and_current_defaults_to_store(): void
    {
        $response = $this->getJson('/api/channels/current');

        $response->assertOk();
        $response->assertJsonPath('suggested.type', 'store');
        $response->assertJsonPath('suggested.name', 'Toko');
        $response->assertJsonPath('active_event', null);
        $this->assertNotNull(SalesChannel::store()->id);
    }

    public function test_creating_event_adds_linked_closure_and_surfaces_as_current(): void
    {
        $response = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('channel.type', 'event');
        $response->assertJsonPath('channel.status', 'active');

        $this->assertDatabaseHas('operating_closures', [
            'label' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ]);

        $current = $this->getJson('/api/channels/current');
        $current->assertJsonPath('suggested.name', 'Bazaar Senayan');
        $current->assertJsonPath('active_event.name', 'Bazaar Senayan');
    }

    public function test_overlapping_events_are_rejected(): void
    {
        $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar A',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->assertCreated();

        $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar B',
            'starts_at' => '2026-09-25',
            'ends_at' => '2026-09-26',
        ])->assertUnprocessable();
    }

    public function test_event_catalog_is_separate_from_store_and_price_override_applies(): void
    {
        $storeMenu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 15000,
            'is_available' => true,
        ]);

        $event = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->json('channel');

        $this->postJson("/api/channels/{$event['id']}/menus/assign", [
            'menus' => [
                ['menu_id' => $storeMenu->id, 'price_override' => 20000],
            ],
        ])->assertOk();

        $storeCatalog = $this->getJson('/api/menu');
        $storeCatalog->assertJsonFragment(['id' => $storeMenu->id, 'price' => 15000]);

        $eventCatalog = $this->getJson('/api/menu?sales_channel_id='.$event['id']);
        $eventCatalog->assertJsonFragment([
            'id' => $storeMenu->id,
            'price' => 20000,
            'price_label' => 'Rp 20.000',
        ]);
    }

    public function test_toggle_availability_is_per_channel(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 15000,
            'is_available' => true,
        ]);

        $eventId = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->json('channel.id');

        $this->postJson("/api/channels/{$eventId}/menus/assign", [
            'menus' => [['menu_id' => $menu->id]],
        ])->assertOk();

        $this->postJson("/api/menu/toggle-availability/{$menu->id}?sales_channel_id={$eventId}")
            ->assertOk()
            ->assertJsonPath('menu.is_available', false);

        $this->getJson('/api/menu')->assertJsonFragment([
            'id' => $menu->id,
            'is_available' => true,
        ]);
    }

    public function test_transactions_and_numbers_are_scoped_per_channel(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 15000,
            'is_available' => true,
        ]);

        $eventId = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->json('channel.id');

        $this->postJson("/api/channels/{$eventId}/menus/assign", [
            'menus' => [['menu_id' => $menu->id]],
        ])->assertOk();

        $storeTxn = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Toko Guest',
            'customer_phone' => '081111111111',
            'customer_phone_country' => 'ID',
            'items' => [['menu_id' => $menu->id, 'quantity' => 1]],
        ]);
        $storeTxn->assertCreated();
        $storeTxn->assertJsonPath('transaction.transaction_number', '001');
        $storeTxn->assertJsonPath('transaction.sales_channel_type', 'store');

        $eventTxn = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Bazaar Guest',
            'customer_phone' => '082222222222',
            'customer_phone_country' => 'ID',
            'sales_channel_id' => $eventId,
            'items' => [['menu_id' => $menu->id, 'quantity' => 1]],
        ]);
        $eventTxn->assertCreated();
        $eventTxn->assertJsonPath('transaction.transaction_number', '001');
        $eventTxn->assertJsonPath('transaction.sales_channel_type', 'event');

        $this->getJson('/api/transaction/today')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Toko Guest'])
            ->assertJsonMissing(['name' => 'Bazaar Guest']);

        $this->getJson('/api/transaction/today?sales_channel_id='.$eventId)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Bazaar Guest'])
            ->assertJsonMissing(['name' => 'Toko Guest']);
    }

    public function test_reports_do_not_mix_store_and_event_sales(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Edamame',
            'price' => 15000,
            'is_available' => true,
        ]);

        $eventId = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->json('channel.id');

        $this->postJson("/api/channels/{$eventId}/menus/assign", [
            'menus' => [['menu_id' => $menu->id]],
        ])->assertOk();

        $storeTxnId = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Toko Guest',
            'customer_phone' => '081111111111',
            'customer_phone_country' => 'ID',
            'items' => [['menu_id' => $menu->id, 'quantity' => 2]],
        ])->json('transaction.id');
        $this->postJson("/api/transaction/mark-paid/{$storeTxnId}")->assertOk();

        $eventTxnId = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Bazaar Guest',
            'customer_phone' => '082222222222',
            'customer_phone_country' => 'ID',
            'sales_channel_id' => $eventId,
            'items' => [['menu_id' => $menu->id, 'quantity' => 1]],
        ])->json('transaction.id');
        $this->postJson("/api/transaction/mark-paid/{$eventTxnId}")->assertOk();

        $storeReport = $this->getJson('/api/report/sales?preset=today');
        $storeReport->assertJsonPath('summary.paid_count', 1);
        $storeReport->assertJsonPath('summary.revenue', 30000);

        $eventReport = $this->getJson('/api/report/sales?preset=today&sales_channel_id='.$eventId);
        $eventReport->assertJsonPath('summary.paid_count', 1);
        $eventReport->assertJsonPath('summary.revenue', 15000);
    }

    public function test_weight_based_line_uses_grams_and_does_not_scale_addons(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Salmon Sashimi',
            'price' => 15000,
            'pricing_type' => 'weight_based',
            'is_available' => true,
        ]);

        $group = $menu->addonGroups()->create([
            'name' => 'Sauce',
            'selection_type' => 'single',
            'is_required' => false,
            'sort_order' => 0,
        ]);
        $option = $group->options()->create([
            'name' => 'Spicy',
            'price' => 2000,
            'is_available' => true,
            'sort_order' => 0,
        ]);

        $response = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Alex',
            'customer_phone' => '081234567890',
            'customer_phone_country' => 'ID',
            'items' => [[
                'menu_id' => $menu->id,
                'weight_grams' => 250,
                'addon_option_ids' => [$option->id],
            ]],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('transaction.order_items.0.weight_grams', 250);
        $response->assertJsonPath('transaction.order_items.0.menu.type', 'weight_based');
        $response->assertJsonPath('transaction.total_amount', '39500');
    }

    public function test_weight_based_price_scales_to_actual_grams(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Tuna Sashimi',
            'price' => 20000,
            'pricing_type' => 'weight_based',
            'is_available' => true,
        ]);

        $response = $this->postJson('/api/transaction/create', [
            'customer_name' => 'Alex',
            'customer_phone' => '081234567890',
            'customer_phone_country' => 'ID',
            'items' => [[
                'menu_id' => $menu->id,
                'weight_grams' => 67,
            ]],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('transaction.order_items.0.weight_grams', 67);
        $response->assertJsonPath('transaction.order_items.0.quantity', 1);
        $response->assertJsonPath('transaction.order_items.0.line_total', '13400');
        $response->assertJsonPath('transaction.total_amount', '13400');
    }

    public function test_cannot_order_store_only_menu_on_event_channel(): void
    {
        $menu = MenuModel::query()->create([
            'name' => 'Omakase Set',
            'price' => 99000,
            'is_available' => true,
        ]);

        $eventId = $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->json('channel.id');

        $this->postJson('/api/transaction/create', [
            'customer_name' => 'Alex',
            'customer_phone' => '081234567890',
            'customer_phone_country' => 'ID',
            'sales_channel_id' => $eventId,
            'items' => [['menu_id' => $menu->id, 'quantity' => 1]],
        ])->assertUnprocessable();
    }

    public function test_event_linked_closure_cannot_be_deleted_from_hours(): void
    {
        $this->postJson('/api/channels/events/create', [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-25',
        ])->assertCreated();

        $closure = OperatingClosure::query()->firstOrFail();

        $this->postJson("/api/hours/closures/delete/{$closure->id}")
            ->assertUnprocessable();
    }

    public function test_show_event_includes_archived_events(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-18 10:00:00', StoreHoursService::TIMEZONE));

        $eventId = $this->postJson('/api/channels/events/create', [
            'name' => 'Old Bazaar',
            'starts_at' => '2026-09-20',
            'ends_at' => '2026-09-22',
        ])->json('channel.id');

        Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00', StoreHoursService::TIMEZONE));
        $this->postJson("/api/channels/events/archive/{$eventId}")->assertOk();

        $this->getJson("/api/channels/events/{$eventId}")
            ->assertOk()
            ->assertJsonPath('channel.id', $eventId)
            ->assertJsonPath('channel.is_archived', true);

        $this->postJson("/api/channels/events/unarchive/{$eventId}")
            ->assertOk()
            ->assertJsonPath('channel.is_archived', false);

        $this->assertDatabaseHas('sales_channels', [
            'id' => $eventId,
            'archived_at' => null,
        ]);
    }
}
