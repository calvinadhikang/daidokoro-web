<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrisPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManjoQrisNotificationController extends Controller
{
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
        );

        return response()->json($result['body'], $result['status']);
    }
}
