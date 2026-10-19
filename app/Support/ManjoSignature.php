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
