<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_test_notification_returns_success(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    ['status' => 'ok', 'id' => 'abc-123'],
                ],
            ]),
        ]);

        $response = $this->postJson('/api/notification/test', [
            'token' => 'ExponentPushToken[test-token]',
            'title' => 'Hello',
            'body' => 'World',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Test push notification sent.',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send'
                && $request['to'] === 'ExponentPushToken[test-token]'
                && $request['title'] === 'Hello'
                && $request['body'] === 'World'
                && $request['data']['screen'] === 'index';
        });
    }

    public function test_send_test_notification_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/notification/test', [
            'token' => 'not-a-valid-token',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['token']);
    }

    public function test_send_test_notification_returns_error_when_expo_rejects(): void
    {
        Http::fake([
            'exp.host/*' => Http::response([
                'data' => [
                    [
                        'status' => 'error',
                        'message' => 'DeviceNotRegistered',
                    ],
                ],
            ]),
        ]);

        $response = $this->postJson('/api/notification/test', [
            'token' => 'ExponentPushToken[expired-token]',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'DeviceNotRegistered',
        ]);
    }
}
