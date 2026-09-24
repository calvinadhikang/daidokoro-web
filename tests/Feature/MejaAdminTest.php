<?php

namespace Tests\Feature;

use App\Models\Meja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MejaAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_tables(): void
    {
        Meja::query()->create(['code' => 'A1']);
        Meja::query()->create(['code' => 'B2']);

        $response = $this->get(route('admin.mejas.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/mejas/index')
            ->has('mejas', 2)
            ->where('mejas.0.code', 'A1')
            ->where('mejas.1.code', 'B2')
        );
    }

    public function test_store_creates_table(): void
    {
        $response = $this->post(route('admin.mejas.store'), [
            'code' => ' VIP-1 ',
        ]);

        $meja = Meja::query()->first();
        $this->assertNotNull($meja);
        $this->assertSame('VIP-1', $meja->code);

        $response->assertRedirect(route('admin.mejas.show', $meja));
        $this->assertDatabaseHas('mejas', ['code' => 'VIP-1']);
    }

    public function test_store_requires_unique_code(): void
    {
        Meja::query()->create(['code' => 'A1']);

        $response = $this->post(route('admin.mejas.store'), [
            'code' => 'A1',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseCount('mejas', 1);
    }

    public function test_show_includes_qr_url(): void
    {
        $meja = Meja::query()->create(['code' => 'A1']);

        $response = $this->get(route('admin.mejas.show', $meja));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/mejas/show')
            ->where('meja.code', 'A1')
            ->where('qrUrl', route('table.entry', ['code' => 'A1']))
        );
    }

    public function test_update_changes_code(): void
    {
        $meja = Meja::query()->create(['code' => 'A1']);

        $response = $this->put(route('admin.mejas.update', $meja), [
            'code' => 'A2',
        ]);

        $response->assertRedirect(route('admin.mejas.show', $meja));
        $this->assertDatabaseHas('mejas', ['id' => $meja->id, 'code' => 'A2']);
    }

    public function test_destroy_deletes_table(): void
    {
        $meja = Meja::query()->create(['code' => 'A1']);

        $response = $this->delete(route('admin.mejas.destroy', $meja));

        $response->assertRedirect(route('admin.mejas.index'));
        $this->assertDatabaseMissing('mejas', ['id' => $meja->id]);
    }
}
