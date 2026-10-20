<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrisPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ManjoQrisNotificationController extends Controller
{
    public function token(Request $request, QrisPaymentService $payments): JsonResponse
    {
        $result = $payments->issueAccessToken(
            (string) $request->header('X-CLIENT-KEY', ''),
            (string) $request->header('X-TIMESTAMP', ''),
            (string) $request->header('X-SIGNATURE', ''),
            $request->getContent(),
        );

        Log::info('Manjo QRIS access token', [
            'path' => $request->path(),
            'response_code' => $result['body']['responseCode'] ?? null,
        ]);

        return response()->json($result['body'], $result['status']);
    }

    public function store(Request $request, QrisPaymentService $payments): JsonResponse
    {
        $authorization = (string) $request->header('Authorization', '');
        $accessToken = str_starts_with($authorization, 'Bearer ')
            ? trim(substr($authorization, 7))
            : '';

        $result = $payments->handleNotification(
            $request->getContent(),
            (string) $request->header('X-TIMESTAMP', ''),
            (string) $request->header('X-SIGNATURE', ''),
            $accessToken,
            $request->getPathInfo(),
        );

        Log::info('Manjo QRIS notification', [
            'path' => $request->path(),
            'response_code' => $result['body']['responseCode'] ?? null,
        ]);

        return response()->json($result['body'], $result['status']);
    }
}
