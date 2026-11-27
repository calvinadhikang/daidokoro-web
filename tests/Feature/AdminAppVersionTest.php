<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAppVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_receive_the_app_version(): void
    {
        config(['app.version' => 'abc1234']);

        $this->actingAsAdmin();

        $this->get(route('admin.menus.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('appVersion', 'abc1234')
            );
    }
}
