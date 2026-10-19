<?php

namespace App\Support;

use RuntimeException;

class ManjoSignature
{
    public static function token(string $clientId, string $timestamp, string $privateKeyPem): string
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

        return bin2hex($signature);
    }

    public static function request(
        string $method,
        string $endpointUrl,
        string $accessToken,
        string $body,
        string $timestamp,
        string $clientSecret,
    ): string {
        $stringToSign = strtoupper($method).':'.$endpointUrl.':'.$accessToken.':'.hash('sha256', $body).':'.$timestamp;

        return hash_hmac('sha512', $stringToSign, $clientSecret);
    }
}
