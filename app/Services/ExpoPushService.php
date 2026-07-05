<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushService
{
    private const PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->post(self::PUSH_URL, [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'data' => $data,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseResponse(Response $response): array
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                'Expo push API request failed: HTTP '.$response->status()
            );
        }

        /** @var array<string, mixed> $payload */
        $payload = $response->json();

        /** @var list<array<string, mixed>> $results */
        $results = $payload['data'] ?? [];

        if ($results === []) {
            throw new RuntimeException('Expo push API returned an empty response.');
        }

        $first = $results[0];

        if (($first['status'] ?? null) === 'error') {
            $message = is_string($first['message'] ?? null)
                ? $first['message']
                : 'Expo rejected the push notification.';

            throw new RuntimeException($message);
        }

        return $payload;
    }
}
