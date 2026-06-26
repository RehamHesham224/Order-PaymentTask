<?php

namespace Database\Seeders;

use App\Models\PaymentGatewayConfig;
use App\Payments\Gateways\CreditCardGateway;
use App\Payments\Gateways\PayPalGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        PaymentGatewayConfig::updateOrCreate(
            ['name' => 'credit_card'],
            [
                'driver_class' => CreditCardGateway::class,
                'is_active' => true,
                'config' => [
                    'api_key' => env('PAYMENT_CREDIT_CARD_API_KEY'),
                    'secret' => env('PAYMENT_CREDIT_CARD_SECRET'),
                ],
            ],
        );

        PaymentGatewayConfig::updateOrCreate(
            ['name' => 'paypal'],
            [
                'driver_class' => PayPalGateway::class,
                'is_active' => true,
                'config' => [
                    'client_id' => env('PAYPAL_CLIENT_ID'),
                    'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                ],
            ],
        );
    }
}
