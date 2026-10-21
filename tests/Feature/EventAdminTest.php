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

        $this->actingAsAdmin();
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

    public function test_admin_can_archive_and_restore_event(): void
    {
        $event = $this->post(route('admin.events.store'), [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-24',
        ]);

        $channel = SalesChannel::query()->events()->firstOrFail();
        $event->assertRedirect(route('admin.events.show', $channel));

        $this->post(route('admin.events.archive', $channel))
            ->assertRedirect(route('admin.events.index'));

        $this->get(route('admin.events.show', $channel))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/show')
                ->where('channel.is_archived', true)
            );

        $this->get(route('admin.events.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/index')
                ->where('channels.0.is_archived', true)
            );

        $this->post(route('admin.events.unarchive', $channel))
            ->assertRedirect(route('admin.events.show', $channel));

        $this->assertDatabaseHas('sales_channels', [
            'id' => $channel->id,
            'archived_at' => null,
        ]);
    }

    public function test_admin_can_create_menu_sold_only_at_the_event(): void
    {
        $this->post(route('admin.events.store'), [
            'name' => 'Bazaar Senayan',
            'starts_at' => '2026-09-23',
            'ends_at' => '2026-09-24',
        ]);

        $event = SalesChannel::query()->events()->firstOrFail();
        $store = SalesChannel::store();

        $this->get(route('admin.events.menus.create', $event))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/menu-create')
                ->where('channel.id', $event->id)
            );

        $this->post(route('admin.events.menus.store', $event), [
            'name' => 'Event Ramen',
            'price' => 45000,
            'is_available' => true,
        ])->assertRedirect(route('admin.events.show', $event));

        $menu = MenuModel::query()->where('name', 'Event Ramen')->firstOrFail();

        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $event->id,
            'menu_id' => $menu->id,
        ]);
        $this->assertDatabaseMissing('channel_menu', [
            'sales_channel_id' => $store->id,
            'menu_id' => $menu->id,
        ]);
    }

    public function test_duplicate_page_prefills_the_source_event(): void
    {
        $source = $this->makeEvent('Bazaar Senayan', '2026-08-01', '2026-08-02', [
            ['name' => 'Chicken Rice', 'price' => 35000, 'price_override' => 40000],
        ]);

        $this->get(route('admin.events.create', ['from' => $source->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/create')
                ->where('sourceEventId', $source->id)
                ->where('defaults.name', 'Bazaar Senayan')
                ->where('events.0.id', $source->id)
                ->where('events.0.menus_count', 1)
                ->missing('events.0.menus')
            );
    }

    public function test_admin_can_duplicate_an_event_with_the_same_menus(): void
    {
        $source = $this->makeEvent('Bazaar Senayan', '2026-08-01', '2026-08-02', [
            [
                'name' => 'Chicken Rice',
                'price' => 35000,
                'price_override' => 40000,
                'is_available' => false,
            ],
            [
                'name' => 'Event Ramen',
                'price' => 45000,
                'price_override' => 50000,
                'event_only' => true,
            ],
        ]);

        $this->post(route('admin.events.store'), [
            'name' => 'Bazaar Bandung',
            'starts_at' => '2026-10-01',
            'ends_at' => '2026-10-02',
            'source_event_id' => $source->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Event created with 2 menus from Bazaar Senayan.');

        $copy = SalesChannel::query()->where('name', 'Bazaar Bandung')->firstOrFail();
        $rice = MenuModel::query()->where('name', 'Chicken Rice')->firstOrFail();
        $ramen = MenuModel::query()->where('name', 'Event Ramen')->firstOrFail();

        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $copy->id,
            'menu_id' => $rice->id,
            'price_override' => 40000,
            'is_available' => false,
        ]);
        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $copy->id,
            'menu_id' => $ramen->id,
            'price_override' => 50000,
            'is_available' => true,
        ]);
        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $source->id,
            'menu_id' => $rice->id,
            'price_override' => 40000,
            'is_available' => false,
        ]);
        $this->assertDatabaseMissing('channel_menu', [
            'sales_channel_id' => SalesChannel::store()->id,
            'menu_id' => $ramen->id,
        ]);
        $this->assertDatabaseHas('operating_closures', [
            'sales_channel_id' => $copy->id,
            'label' => 'Bazaar Bandung',
        ]);
    }

    public function test_duplicating_an_event_with_no_menus_creates_an_empty_event(): void
    {
        $source = $this->makeEvent('Empty Bazaar', '2026-08-01', '2026-08-02');

        $this->post(route('admin.events.store'), [
            'name' => 'Next Bazaar',
            'starts_at' => '2026-10-01',
            'ends_at' => '2026-10-02',
            'source_event_id' => $source->id,
        ])->assertSessionHas('success', 'Event created. Empty Bazaar has no menus to copy.');

        $copy = SalesChannel::query()->where('name', 'Next Bazaar')->firstOrFail();
        $this->assertSame(0, $copy->menus()->count());
    }

    public function test_store_channel_cannot_be_used_as_a_menu_source(): void
    {
        $this->post(route('admin.events.store'), [
            'name' => 'Copy',
            'starts_at' => '2026-10-01',
            'ends_at' => '2026-10-02',
            'source_event_id' => SalesChannel::store()->id,
        ])->assertSessionHasErrors('source_event_id');

        $this->assertDatabaseMissing('sales_channels', ['name' => 'Copy']);
    }

    public function test_show_page_lists_menus_from_other_events(): void
    {
        $source = $this->makeEvent('Past Bazaar', '2026-08-01', '2026-08-02', [
            ['name' => 'Ramen', 'price' => 45000, 'price_override' => 48000],
            ['name' => 'Chicken Rice', 'price' => 35000],
        ]);
        $source->update(['archived_at' => Carbon::now(StoreHoursService::TIMEZONE)]);

        $current = $this->makeEvent('Tonight', '2026-10-01', '2026-10-02');

        $this->get(route('admin.events.show', $current))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/events/show')
                ->has('copySources', 1)
                ->where('copySources.0.id', $source->id)
                ->where('copySources.0.is_archived', true)
                ->where('copySources.0.menus_count', 2)
                ->where('copySources.0.menus.0.name', 'Chicken Rice')
                ->where('copySources.0.menus.0.price_label', 'Rp 35.000')
                ->where('copySources.0.menus.1.name', 'Ramen')
                ->where('copySources.0.menus.1.price_override', 48000)
                ->where('copySources.0.menus.1.price_label', 'Rp 48.000')
            );
    }

    public function test_admin_can_copy_selected_menus_from_another_event(): void
    {
        $source = $this->makeEvent('Past Bazaar', '2026-08-01', '2026-08-02', [
            [
                'name' => 'Chicken Rice',
                'price' => 35000,
                'price_override' => 40000,
                'is_available' => false,
            ],
            ['name' => 'Ramen', 'price' => 45000, 'price_override' => 50000],
            ['name' => 'Gyoza', 'price' => 25000],
        ]);
        $target = $this->makeEvent('Next Bazaar', '2026-10-01', '2026-10-02');

        $sourceRice = $source->menus()->where('name', 'Chicken Rice')->firstOrFail();
        $ramen = $source->menus()->where('name', 'Ramen')->firstOrFail();
        $gyoza = $source->menus()->where('name', 'Gyoza')->firstOrFail();
        $target->menus()->attach($sourceRice->id, [
            'price_override' => 10000,
            'is_available' => true,
        ]);

        $this->post(route('admin.events.menus.copy', $target), [
            'source_event_id' => $source->id,
            'menu_ids' => [$sourceRice->id, $ramen->id],
        ])
            ->assertRedirect(route('admin.events.show', $target))
            ->assertSessionHas('success', 'Added 1 menu from Past Bazaar.');

        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $target->id,
            'menu_id' => $sourceRice->id,
            'price_override' => 10000,
            'is_available' => true,
        ]);
        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $target->id,
            'menu_id' => $ramen->id,
            'price_override' => 50000,
            'is_available' => true,
        ]);
        $this->assertDatabaseMissing('channel_menu', [
            'sales_channel_id' => $target->id,
            'menu_id' => $gyoza->id,
        ]);
        $this->assertDatabaseHas('channel_menu', [
            'sales_channel_id' => $source->id,
            'menu_id' => $sourceRice->id,
            'price_override' => 40000,
            'is_available' => false,
        ]);
    }

    public function test_copy_rejects_menus_that_are_not_on_the_source_event(): void
    {
        $source = $this->makeEvent('Past Bazaar', '2026-08-01', '2026-08-02', [
            ['name' => 'Ramen', 'price' => 45000],
        ]);
        $target = $this->makeEvent('Next Bazaar', '2026-10-01', '2026-10-02');
        $storeMenu = MenuModel::query()->create([
            'name' => 'Store Only',
            'price' => 10000,
            'is_available' => true,
        ]);

        $this->from(route('admin.events.show', $target))
            ->post(route('admin.events.menus.copy', $target), [
                'source_event_id' => $source->id,
                'menu_ids' => [$storeMenu->id],
            ])
            ->assertRedirect(route('admin.events.show', $target))
            ->assertSessionHasErrors('menu_ids');

        $this->assertDatabaseMissing('channel_menu', [
            'sales_channel_id' => $target->id,
            'menu_id' => $storeMenu->id,
        ]);
    }

    public function test_copy_rejects_the_current_event(): void
    {
        $event = $this->makeEvent('Tonight', '2026-10-01', '2026-10-02', [
            ['name' => 'Ramen', 'price' => 45000],
        ]);
        $menu = $event->menus()->firstOrFail();

        $this->from(route('admin.events.show', $event))
            ->post(route('admin.events.menus.copy', $event), [
                'source_event_id' => $event->id,
                'menu_ids' => [$menu->id],
            ])
            ->assertRedirect(route('admin.events.show', $event))
            ->assertSessionHasErrors('source_event_id');

        $this->assertSame(1, $event->menus()->count());
    }

    /**
     * @param  list<array{name: string, price: int, price_override?: int|null, is_available?: bool, event_only?: bool}>  $menus
     */
    private function makeEvent(string $name, string $startsAt, string $endsAt, array $menus = []): SalesChannel
    {
        $event = SalesChannel::query()->create([
            'type' => SalesChannel::TYPE_EVENT,
            'name' => $name,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'closes_store' => true,
        ]);

        foreach ($menus as $row) {
            $menu = new MenuModel([
                'name' => $row['name'],
                'price' => $row['price'],
                'is_available' => true,
            ]);

            if ($row['event_only'] ?? false) {
                $menu->assignToSalesChannelId = $event->id;
            }

            $menu->save();

            $pivot = [
                'price_override' => $row['price_override'] ?? null,
                'is_available' => $row['is_available'] ?? true,
            ];

            if ($row['event_only'] ?? false) {
                $event->menus()->updateExistingPivot($menu->id, $pivot);
            } else {
                $event->menus()->attach($menu->id, $pivot);
            }
        }

        return $event->fresh() ?? $event;
    }
}
