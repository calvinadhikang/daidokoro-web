<?php

namespace Tests\Feature;

use App\Models\PushDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushDeviceRegistrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_push_device_creates_record(): void
    {
        $response = $this->postJson('/api/notification/register', [
            'device_key' => 'cashier-tablet-1',
            'token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
            'device_name' => 'Pixel 4a',
            'app_version' => '1.0.0',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Push device registered.',
            'device' => [
                'device_key' => 'cashier-tablet-1',
                'platform' => 'android',
                'device_name' => 'Pixel 4a',
            ],
        ]);

        $this->assertDatabaseHas('push_devices', [
            'device_key' => 'cashier-tablet-1',
            'expo_push_token' => 'ExponentPushToken[abc123]',
            'platform' => 'android',
            'device_name' => 'Pixel 4a',
            'app_version' => '1.0.0',
        ]);
    }

    public function test_register_push_device_updates_existing_device_by_key(): void
    {
        PushDevice::query()->create([
            'device_key' => 'cashier-tablet-1',
            'expo_push_token' => 'ExponentPushToken[old-token]',
            'platform' => 'android',
            'device_name' => 'Old name',
            'app_version' => '0.9.0',
            'last_registered_at' => now()->subDay(),
        ]);

        $response = $this->postJson('/api/notification/register', [
            'device_key' => 'cashier-tablet-1',
            'token' => 'ExponentPushToken[new-token]',
            'platform' => 'android',
            'device_name' => 'Pixel 4a',
            'app_version' => '1.0.0',
        ]);

        $response->assertOk();

        $this->assertDatabaseCount('push_devices', 1);
        $this->assertDatabaseHas('push_devices', [
            'device_key' => 'cashier-tablet-1',
            'expo_push_token' => 'ExponentPushToken[new-token]',
            'device_name' => 'Pixel 4a',
            'app_version' => '1.0.0',
        ]);
    }

    public function test_register_push_device_validates_payload(): void
    {
        $response = $this->postJson('/api/notification/register', [
            'device_key' => '',
            'token' => 'invalid-token',
            'platform' => 'windows',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['device_key', 'token', 'platform']);
    }
}
