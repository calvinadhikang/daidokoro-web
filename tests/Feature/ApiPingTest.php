<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_ok_response(): void
    {
        $response = $this->getJson('/api/ping');

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'message' => 'pong',
        ]);
        $response->assertJsonStructure(['time']);
    }
}
