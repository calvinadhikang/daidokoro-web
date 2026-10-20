<?php

return [

    'base_url' => env('MANJO_BASE_URL', 'https://snap-uat.manjo.co.id/api'),

    'client_id' => env('MANJO_CLIENT_ID'),

    'client_secret' => env('MANJO_CLIENT_SECRET'),

    'merchant_id' => env('MANJO_MERCHANT_ID'),

    'partner_id' => env('MANJO_PARTNER_ID', env('MANJO_MERCHANT_ID')),

    'channel_id' => env('MANJO_CHANNEL_ID', '05'),

    'private_key_path' => env('MANJO_PRIVATE_KEY_PATH'),

    /*
    | Manjo's public key, used to verify the access-token request they send us.
    | Empty uses the key bundled in resources/manjo/provider-public.pem.
    */
    'provider_public_key_path' => env('MANJO_PROVIDER_PUBLIC_KEY_PATH'),

    'origin' => env('MANJO_ORIGIN', 'staging.daidokoro.my.id'),

    /*
    | Public IP Manjo expects in X-IP-ADDRESS. Local and staging differ.
    */
    'client_ip' => env('MANJO_CLIENT_IP'),

    'qr_validity_seconds' => (int) env('MANJO_QR_VALIDITY_SECONDS', 3600),

    /*
    | Extra path accepted in the payment-notification signature.
    | Manjo also signs the SNAP path /v1.0/qr/qr-mpm-notify.
    */
    'notify_path' => env('MANJO_NOTIFY_PATH', '/v1.0/qr/qr-mpm-notify'),

];
