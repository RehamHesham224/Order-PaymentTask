<?php

use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PayPalGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Gateways can be configured via .env (below) and/or the payment_gateways
    | database table. Database config merges with and overrides file/env values
    | for the same gateway name. Run: php artisan db:seed --class=PaymentGatewaySeeder
    |
    */

    'gateways' => [
        'credit_card' => [
            'driver' => CreditCardGateway::class,
            'api_key' => env('PAYMENT_CREDIT_CARD_API_KEY'),
            'secret' => env('PAYMENT_CREDIT_CARD_SECRET'),
        ],
        'paypal' => [
            'driver' => PayPalGateway::class,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        ],
    ],

];
