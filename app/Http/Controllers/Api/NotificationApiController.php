<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpoPushService;
use App\Services\PushDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class NotificationApiController extends Controller
{
    public function __construct(
        private ExpoPushService $expoPush,
        private PushDeviceService $pushDevices,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_key' => ['required', 'string', 'max:120'],
            'token' => ['required', 'string', 'regex:/^ExponentPushToken\[[^\]]+\]$/'],
            'platform' => ['required', 'string', 'in:android,ios'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ]);

        $device = $this->pushDevices->register([
            'device_key' => $validated['device_key'],
            'expo_push_token' => $validated['token'],
            'platform' => $validated['platform'],
            'device_name' => $validated['device_name'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Push device registered.',
            'device' => [
                'id' => $device->id,
                'device_key' => $device->device_key,
                'platform' => $device->platform,
                'device_name' => $device->device_name,
                'last_registered_at' => $device->last_registered_at?->toIso8601String(),
            ],
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'regex:/^ExponentPushToken\[[^\]]+\]$/'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:500'],
        ]);

        $title = $validated['title'] ?? 'Daidokoro test';
        $body = $validated['body'] ?? 'Push notification test from the API.';

        try {
            $expoResponse = $this->expoPush->send(
                token: $validated['token'],
                title: $title,
                body: $body,
                data: [
                    'screen' => 'index',
                    'source' => 'api_test',
                ],
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Test push notification sent.',
            'expo' => $expoResponse,
        ]);
    }
}
