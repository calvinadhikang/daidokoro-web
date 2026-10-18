<?php

namespace Tests\Feature;

use App\Models\OmakaseSession;
use App\Services\StoreHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OmakaseAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsAdmin();
        Carbon::setTestNow(Carbon::parse('2026-10-17 10:00:00', StoreHoursService::TIMEZONE));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_index_lists_sessions_with_menu_preview(): void
    {
        $older = $this->makeSession('Older', '2026-01-01');
        $newer = $this->makeSession('Newer', '2026-12-01', ['OTORO', 'AKAMI']);

        $this->get(route('admin.omakase.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/omakase/index')
                ->where('today', '2026-10-17')
                ->has('sessions', 2)
                ->where('sessions.0.id', $newer->id)
                ->where('sessions.0.menus_count', 2)
                ->where('sessions.0.menu_preview.0', 'OTORO')
                ->where('sessions.1.id', $older->id)
                ->where('sessions.1.menus_count', 0)
            );
    }

    public function test_admin_can_create_a_session(): void
    {
        $response = $this->post(route('admin.omakase.store'), [
            'name' => '  Kaiseki Evening  ',
            'date' => '2026-10-17',
            'source_session_id' => '',
        ]);

        $session = OmakaseSession::query()->firstOrFail();
        $response->assertRedirect(route('admin.omakase.show', $session))
            ->assertSessionHas('success', 'Omakase session created successfully.');

        $this->assertSame('Kaiseki Evening', $session->name);
        $this->assertSame('2026-10-17', substr((string) $session->date, 0, 10));
        $this->assertDatabaseCount('omakase_menu', 0);
    }

    public function test_store_requires_a_name_and_date(): void
    {
        $this->from(route('admin.omakase.create'))
            ->post(route('admin.omakase.store'), [
                'name' => ' ',
                'date' => '',
            ])
            ->assertRedirect(route('admin.omakase.create'))
            ->assertSessionHasErrors(['name', 'date']);

        $this->assertDatabaseCount('omakase_session', 0);
    }

    public function test_create_page_can_start_from_an_existing_session(): void
    {
        $source = $this->makeSession('Summer Omakase', '2026-07-10', ['OTORO']);

        $this->get(route('admin.omakase.create', ['from' => $source->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/omakase/create')
                ->where('sourceSessionId', $source->id)
                ->where('defaults.name', 'Summer Omakase')
                ->where('defaults.date', '2026-10-17')
                ->where('sessions.0.menus_count', 1)
            );
    }

    public function test_admin_can_duplicate_a_session_by_copying_its_menus(): void
    {
        $source = $this->makeSession('Summer Omakase', '2026-07-10', ['A', 'B', 'A']);

        $this->post(route('admin.omakase.store'), [
            'name' => 'Autumn Omakase',
            'date' => '2026-10-17',
            'source_session_id' => $source->id,
        ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Omakase session created with menus from Summer Omakase.');

        $copy = OmakaseSession::query()->where('name', 'Autumn Omakase')->firstOrFail();

        $this->assertSame(
            ['A', 'B', 'A'],
            $copy->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['A', 'B', 'A'],
            $source->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_show_page_includes_menus_and_other_sessions(): void
    {
        $other = $this->makeSession('Other', '2026-08-01', ['SASHIMI']);
        $session = $this->makeSession('Tonight', '2026-10-17', ['OTORO', 'AKAMI']);

        $this->get(route('admin.omakase.show', $session))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/omakase/show')
                ->where('session.name', 'Tonight')
                ->where('session.menus.0.name', 'OTORO')
                ->where('session.menus.1.name', 'AKAMI')
                ->has('importSources', 1)
                ->where('importSources.0.id', $other->id)
            );
    }

    public function test_admin_can_update_and_delete_a_session(): void
    {
        $session = $this->makeSession('Tonight', '2026-10-17', ['OTORO']);

        $this->put(route('admin.omakase.update', $session), [
            'name' => 'Updated',
            'date' => '2026-11-01',
        ])->assertRedirect(route('admin.omakase.show', $session));

        $session->refresh();
        $this->assertSame('Updated', $session->name);
        $this->assertSame('2026-11-01', substr((string) $session->date, 0, 10));

        $this->delete(route('admin.omakase.destroy', $session))
            ->assertRedirect(route('admin.omakase.index'));

        $this->assertDatabaseMissing('omakase_session', ['id' => $session->id]);
        $this->assertDatabaseMissing('omakase_menu', ['omakase_session_id' => $session->id]);
    }

    public function test_admin_can_add_rename_and_delete_a_menu(): void
    {
        $session = $this->makeSession('Tonight', '2026-10-17');

        $this->post(route('admin.omakase.menus.store', $session), [
            'menu_name' => '  OTORO  ',
        ])->assertRedirect(route('admin.omakase.show', $session));

        $menu = $session->omakaseMenus()->firstOrFail();
        $this->assertSame('OTORO', $menu->name);

        $this->put(route('admin.omakase.menus.update', [$session, $menu]), [
            'menu_name' => 'AKAMI',
        ])->assertRedirect(route('admin.omakase.show', $session));

        $this->assertSame('AKAMI', $menu->fresh()->name);

        $this->delete(route('admin.omakase.menus.destroy', [$session, $menu]))
            ->assertRedirect(route('admin.omakase.show', $session));

        $this->assertDatabaseMissing('omakase_menu', ['id' => $menu->id]);
    }

    public function test_menu_from_another_session_cannot_be_changed(): void
    {
        $session = $this->makeSession('Tonight', '2026-10-17', ['OTORO']);
        $other = $this->makeSession('Other', '2026-08-01');
        $menu = $session->omakaseMenus()->firstOrFail();

        $this->put(route('admin.omakase.menus.update', [$other, $menu]), [
            'menu_name' => 'Changed',
        ])->assertNotFound();

        $this->delete(route('admin.omakase.menus.destroy', [$other, $menu]))
            ->assertNotFound();

        $this->assertSame('OTORO', $menu->fresh()->name);
    }

    public function test_admin_can_import_menus_and_skips_names_already_present(): void
    {
        $source = $this->makeSession('Summer Omakase', '2026-07-10', ['A', 'b', 'C']);
        $target = $this->makeSession('Tonight', '2026-10-17', ['B']);

        $this->post(route('admin.omakase.import', $target), [
            'source_session_id' => $source->id,
        ])
            ->assertRedirect(route('admin.omakase.show', $target))
            ->assertSessionHas('success', 'Imported 2 menus from Summer Omakase.');

        $this->assertSame(
            ['B', 'A', 'C'],
            $target->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['A', 'b', 'C'],
            $source->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );

        $this->post(route('admin.omakase.import', $target), [
            'source_session_id' => $source->id,
        ])->assertSessionHas('success', 'No new menus. Those names are already on this session.');

        $this->assertSame(3, $target->omakaseMenus()->count());
    }

    public function test_importing_from_an_empty_session_adds_nothing(): void
    {
        $source = $this->makeSession('Empty', '2026-07-10');
        $target = $this->makeSession('Tonight', '2026-10-17', ['OTORO']);

        $this->post(route('admin.omakase.import', $target), [
            'source_session_id' => $source->id,
        ])->assertSessionHas('success', 'That session has no menus to import.');

        $this->assertSame(['OTORO'], $target->omakaseMenus()->pluck('name')->all());
    }

    public function test_import_rejects_the_current_session(): void
    {
        $session = $this->makeSession('Tonight', '2026-10-17');

        $this->from(route('admin.omakase.show', $session))
            ->post(route('admin.omakase.import', $session), [
                'source_session_id' => $session->id,
            ])
            ->assertRedirect(route('admin.omakase.show', $session))
            ->assertSessionHasErrors('source_session_id');

        $this->assertSame(0, $session->omakaseMenus()->count());
    }

    /**
     * @param  list<string>  $menus
     */
    private function makeSession(string $name, string $date, array $menus = []): OmakaseSession
    {
        $session = OmakaseSession::query()->create([
            'name' => $name,
            'date' => $date,
        ]);

        foreach ($menus as $menu) {
            $session->omakaseMenus()->create(['name' => $menu]);
        }

        return $session;
    }
}
