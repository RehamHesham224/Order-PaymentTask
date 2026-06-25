<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'payment_id' => Str::uuid()->toString(),
            'order_id' => Order::factory()->confirmed(),
            'status' => PaymentStatus::Successful,
            'payment_method' => 'credit_card',
            'gateway' => 'credit_card',
            'amount' => fake()->randomFloat(2, 10, 500),
            'gateway_response' => ['simulated' => true],
        ];
    }
}
