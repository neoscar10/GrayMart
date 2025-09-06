<?php

return [

    'mode' => env('PAYPAL_MODE', 'sandbox'),

    'sandbox' => [
        // accept either PAYPAL_CLIENT_ID / PAYPAL_SECRET or PAYPAL_SANDBOX_CLIENT_ID / PAYPAL_SANDBOX_CLIENT_SECRET
        'client_id'     => env('PAYPAL_CLIENT_ID') ?: env('PAYPAL_SANDBOX_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_SECRET')     ?: env('PAYPAL_SANDBOX_CLIENT_SECRET', ''),
        'app_id'        => env('PAYPAL_APP_ID')     ?: env('PAYPAL_SANDBOX_APP_ID', ''),
    ],

    'live' => [
        'client_id'     => env('PAYPAL_LIVE_CLIENT_ID')     ?: env('PAYPAL_CLIENT_ID', ''),
        'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET') ?: env('PAYPAL_SECRET', ''),
        'app_id'        => env('PAYPAL_LIVE_APP_ID')        ?: env('PAYPAL_APP_ID', ''),
    ],

    'payment_action' => 'Sale',
    'currency'       => env('PAYPAL_CURRENCY', 'USD'),
    'notify_url'     => env('PAYPAL_NOTIFY_URL', ''),
    'locale'         => 'en_US',

    // if you’re on a dev box with broken CA certs, temporarily set PAYPAL_VALIDATE_SSL=false to confirm
    'validate_ssl'   => (bool) env('PAYPAL_VALIDATE_SSL', true),
];
