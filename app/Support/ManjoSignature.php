<?php

namespace App\Support;

use RuntimeException;

class ManjoSignature
{
    public static function token(string $clientId, string $timestamp, string $privateKeyPem, string $encoding = 'base64'): string
    {
        $signed = openssl_sign(
            $clientId.'|'.$timestamp,
            $signature,
            $privateKeyPem,
            OPENSSL_ALGO_SHA256,
        );

        if ($signed !== true) {
            throw new RuntimeException('QRIS signing key could not be read.');
        }

        return $encoding === 'hex' ? bin2hex($signature) : base64_encode($signature);
    }

    public static function verifyToken(string $clientKey, string $timestamp, string $signature, string $publicKeyPem): bool
    {
        $payload = $clientKey.'|'.$timestamp;

        foreach (self::signatureBytes($signature) as $bytes) {
            if (openssl_verify($payload, $bytes, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function signatureBytes(string $signature): array
    {
        $trimmed = trim($signature);
        $candidates = [];
        $decoded = base64_decode($trimmed, true);

        if (is_string($decoded) && $decoded !== '') {
            $candidates[] = $decoded;
        }

        if ($trimmed !== '' && ctype_xdigit($trimmed) && strlen($trimmed) % 2 === 0) {
            $hex = hex2bin($trimmed);

            if (is_string($hex) && $hex !== '') {
                $candidates[] = $hex;
            }
        }

        return $candidates;
    }

    public static function request(
        string $method,
        string $endpointUrl,
        string $accessToken,
        string $body,
        string $timestamp,
        string $clientSecret,
        string $encoding = 'hex',
    ): string {
        $stringToSign = strtoupper($method).':'.$endpointUrl.':'.$accessToken.':'.hash('sha256', $body).':'.$timestamp;
        $binary = hash_hmac('sha512', $stringToSign, $clientSecret, true);

        return $encoding === 'base64' ? base64_encode($binary) : bin2hex($binary);
    }
}
