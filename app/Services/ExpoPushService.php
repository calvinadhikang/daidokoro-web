<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ExpoPushService
{
    private const PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sendToMany(array $tokens, string $title, string $body, array $data = []): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return ['data' => []];
        }

        $response = Http::acceptJson()
            ->asJson()
            ->post(self::PUSH_URL, [
                'to' => $tokens,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'data' => $data,
            ]);

        return $this->parseResponse($response);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function send(string $token, string $title, string $body, array $data = []): array
    {
        return $this->sendToMany([$token], $title, $body, $data);
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

        $errors = [];

        foreach ($results as $result) {
            if (($result['status'] ?? null) !== 'error') {
                continue;
            }

            $errors[] = is_string($result['message'] ?? null)
                ? $result['message']
                : 'Expo rejected the push notification.';
        }

        if ($errors !== [] && count($errors) === count($results)) {
            throw new RuntimeException($errors[0]);
        }

        return $payload;
    }
}
