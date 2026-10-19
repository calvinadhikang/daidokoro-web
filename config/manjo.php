<?php

return [

    'base_url' => env('MANJO_BASE_URL', 'https://snap-uat.manjo.co.id/api'),

    'client_id' => env('MANJO_CLIENT_ID'),

    'client_secret' => env('MANJO_CLIENT_SECRET'),

    'merchant_id' => env('MANJO_MERCHANT_ID'),

    'partner_id' => env('MANJO_PARTNER_ID', env('MANJO_MERCHANT_ID')),

    'channel_id' => env('MANJO_CHANNEL_ID', '05'),

    'private_key_path' => env('MANJO_PRIVATE_KEY_PATH'),

    'origin' => env('MANJO_ORIGIN', 'staging.daidokoro.my.id'),

    /*
    | Public IP Manjo expects in X-IP-ADDRESS. Local and staging differ.
    */
    'client_ip' => env('MANJO_CLIENT_IP'),

    'qr_validity_seconds' => (int) env('MANJO_QR_VALIDITY_SECONDS', 3600),

    /*
    | Path Manjo calls, and the path included in the notification signature.
    */
    'notify_path' => env('MANJO_NOTIFY_PATH', '/api/manjo/qris/notify'),

];
