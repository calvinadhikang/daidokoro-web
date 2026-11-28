<?php

namespace Tests\Feature;

use App\Models\OmakaseSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmakaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_without_a_source_starts_empty(): void
    {
        $response = $this->postJson('/api/omakase/session/create', [
            'name' => 'Kaiseki Evening',
            'date' => '2026-10-17',
        ]);

        $response->assertOk();
        $response->assertJsonPath('name', 'Kaiseki Evening');

        $session = OmakaseSession::query()->firstOrFail();
        $this->assertSame(0, $session->omakaseMenus()->count());
    }

    public function test_create_can_copy_menus_from_another_session(): void
    {
        $source = $this->makeSession('Summer', '2026-07-10', ['OTORO', 'AKAMI']);

        $response = $this->postJson('/api/omakase/session/create', [
            'name' => 'Tonight',
            'date' => '2026-10-17',
            'source_session_id' => $source->id,
        ]);

        $response->assertOk();
        $session = OmakaseSession::query()->where('name', 'Tonight')->firstOrFail();
        $this->assertSame(
            ['OTORO', 'AKAMI'],
            $session->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
        $this->assertSame(
            ['OTORO', 'AKAMI'],
            $source->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_import_copies_new_names_and_skips_existing_ones(): void
    {
        $source = $this->makeSession('Summer', '2026-07-10', ['A', 'b', 'C']);
        $target = $this->makeSession('Tonight', '2026-10-17', ['B']);

        $response = $this->postJson("/api/omakase/session/import/{$target->id}", [
            'source_session_id' => $source->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('copied', 2);
        $this->assertSame(
            ['B', 'A', 'C'],
            $target->omakaseMenus()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_import_rejects_the_current_session(): void
    {
        $session = $this->makeSession('Tonight', '2026-10-17', ['OTORO']);

        $this->postJson("/api/omakase/session/import/{$session->id}", [
            'source_session_id' => $session->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['source_session_id']);

        $this->assertSame(['OTORO'], $session->omakaseMenus()->pluck('name')->all());
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
