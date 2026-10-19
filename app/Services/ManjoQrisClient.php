<?php

namespace App\Services;

use App\Support\ManjoSignature;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ManjoQrisClient
{
    /**
     * @return array{reference_no: string|null, qr_content: string, expires_at: CarbonInterface}
     */
    public function generateQr(string $partnerReferenceNo, int $amount, string $externalId, string $description): array
    {
        $this->ensureConfigured();

        $token = $this->accessToken();
        $body = json_encode([
            'partnerReferenceNo' => $partnerReferenceNo,
            'amount' => [
                'value' => number_format($amount, 2, '.', ''),
                'currency' => 'IDR',
            ],
            'merchantId' => (string) config('manjo.merchant_id'),
            'validityPeriod' => (string) config('manjo.qr_validity_seconds'),
            'additionalInfo' => [
                'paymentId' => '99',
                'dynamicAmount' => 'N',
                'prodDesc' => mb_substr($description, 0, 100),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            throw new RuntimeException('Could not create a QR code.');
        }

        $timestamp = $this->timestamp();
        $path = $this->signedPath('/v1.0/qr/qr-mpm-generate');
        $signature = ManjoSignature::request(
            'POST',
            $path,
            $token,
            $body,
            $timestamp,
            (string) config('manjo.client_secret'),
        );

        try {
            $response = Http::withHeaders($this->transactionHeaders($token, $timestamp, $signature, $externalId))
                ->withBody($body, 'application/json')
                ->timeout(20)
                ->post($this->url('/v1.0/qr/qr-mpm-generate'));
        } catch (ConnectionException) {
            throw new RuntimeException('Payment service is unreachable. Try again.');
        }

        $code = (string) $response->json('responseCode');

        if (! $response->successful() || ! str_starts_with($code, '200')) {
            Log::warning('Manjo QRIS generate failed', [
                'response_code' => $code,
                'message' => $response->json('responseMessage'),
            ]);

            throw new RuntimeException($this->safeMessage((string) $response->json('responseMessage')));
        }

        $qrContent = (string) $response->json('qrContent');

        if ($qrContent === '' || $qrContent === '-') {
            throw new RuntimeException('Could not create a QR code.');
        }

        $referenceNo = $response->json('referenceNo');

        return [
            'reference_no' => is_string($referenceNo) ? $referenceNo : null,
            'qr_content' => $qrContent,
            'expires_at' => $this->expiresAt($response->json('additionalInfo')),
        ];
    }

    private function accessToken(): string
    {
        $cacheKey = 'manjo.qris.token.'.(string) config('manjo.client_id');
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $privateKey = file_get_contents((string) config('manjo.private_key_path'));

        if ($privateKey === false || openssl_pkey_get_private($privateKey) === false) {
            throw new RuntimeException('QRIS signing key could not be read.');
        }

        $timestamp = $this->timestamp();
        $clientId = (string) config('manjo.client_id');
        $body = '{"grantType":"client_credentials"}';

        try {
            $response = Http::withHeaders([
                'X-TIMESTAMP' => $timestamp,
                'X-CLIENT-KEY' => $clientId,
                'X-SIGNATURE' => ManjoSignature::token($clientId, $timestamp, $privateKey),
            ])
                ->withBody($body, 'application/json')
                ->timeout(20)
                ->post($this->url('/v1.0/access-token/b2b'));
        } catch (ConnectionException) {
            throw new RuntimeException('Payment service is unreachable. Try again.');
        }

        $code = (string) $response->json('responseCode');
        $token = $response->json('accessToken');

        if (! $response->successful() || ! str_starts_with($code, '200') || ! is_string($token) || $token === '') {
            Log::warning('Manjo QRIS token failed', [
                'response_code' => $code,
                'message' => $response->json('responseMessage'),
            ]);

            throw new RuntimeException($this->safeMessage((string) $response->json('responseMessage')));
        }

        $expiresIn = (int) $response->json('expiresIn', 900);
        Cache::put($cacheKey, $token, max(30, $expiresIn - 60));

        return $token;
    }

    private function ensureConfigured(): void
    {
        $missing = [];

        foreach (['base_url', 'client_id', 'client_secret', 'merchant_id', 'partner_id', 'channel_id', 'private_key_path', 'client_ip'] as $key) {
            if (blank(config('manjo.'.$key))) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            Log::error('Manjo QRIS is not configured', ['missing' => $missing]);

            throw new RuntimeException('QRIS is not configured.');
        }

        if (! is_readable((string) config('manjo.private_key_path'))) {
            throw new RuntimeException('QRIS signing key could not be read.');
        }
    }

    /**
     * @return array<string, string>
     */
    private function transactionHeaders(string $token, string $timestamp, string $signature, string $externalId): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$token,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature,
            'X-PARTNER-ID' => (string) config('manjo.partner_id'),
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID' => (string) config('manjo.channel_id'),
            'X-IP-ADDRESS' => (string) config('manjo.client_ip'),
        ];

        $origin = (string) config('manjo.origin');

        if ($origin !== '') {
            $headers['ORIGIN'] = $origin;
        }

        return $headers;
    }

    private function signedPath(string $endpoint): string
    {
        $basePath = parse_url((string) config('manjo.base_url'), PHP_URL_PATH);
        $basePath = is_string($basePath) ? rtrim($basePath, '/') : '';

        return $basePath.$endpoint;
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('manjo.base_url'), '/').$endpoint;
    }

    private function timestamp(): string
    {
        return now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
    }

    private function expiresAt(mixed $additionalInfo): CarbonInterface
    {
        $expireDate = is_array($additionalInfo) ? ($additionalInfo['expireDate'] ?? null) : null;

        if (is_string($expireDate) && preg_match('/^\d{14}$/', $expireDate) === 1) {
            try {
                $parsed = Carbon::createFromFormat('YmdHis', $expireDate, 'Asia/Jakarta');

                if ($parsed instanceof CarbonInterface) {
                    return $parsed;
                }
            } catch (\Throwable) {
                // Fall back to the configured validity window.
            }
        }

        return now()->addSeconds((int) config('manjo.qr_validity_seconds'));
    }

    private function safeMessage(string $message): string
    {
        $message = trim($message);

        if ($message === '') {
            return 'Could not create a QR code.';
        }

        return 'Could not create a QR code. '.mb_substr($message, 0, 150);
    }
}
