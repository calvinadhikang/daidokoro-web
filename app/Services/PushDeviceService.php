<?php

namespace App\Services;

use App\Models\PushDevice;

class PushDeviceService
{
    /**
     * @param  array{
     *     device_key: string,
     *     expo_push_token: string,
     *     platform: string,
     *     device_name?: string|null,
     *     app_version?: string|null,
     * }  $payload
     */
    public function register(array $payload): PushDevice
    {
        PushDevice::query()
            ->where('expo_push_token', $payload['expo_push_token'])
            ->where('device_key', '!=', $payload['device_key'])
            ->delete();

        return PushDevice::query()->updateOrCreate(
            ['device_key' => $payload['device_key']],
            [
                'expo_push_token' => $payload['expo_push_token'],
                'platform' => $payload['platform'],
                'device_name' => $payload['device_name'] ?? null,
                'app_version' => $payload['app_version'] ?? null,
                'last_registered_at' => now(),
            ],
        );
    }
}
