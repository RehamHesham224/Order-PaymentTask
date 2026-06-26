<?php

namespace Database\Factories;

use App\Models\PaymentGatewayConfig;
use App\Payments\Gateways\CreditCardGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentGatewayConfig> */
class PaymentGatewayConfigFactory extends Factory
{
    protected $model = PaymentGatewayConfig::class;

    public function definition(): array
    {
        return [
            'name' => 'credit_card',
            'driver_class' => CreditCardGateway::class,
            'is_active' => true,
            'config' => [
                'api_key' => 'db_test_api_key',
                'secret' => 'db_test_secret',
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
