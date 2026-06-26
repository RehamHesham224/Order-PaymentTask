<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\PaymentGatewayConfig;
use Tests\ApiTestCase;

class PaymentDatabaseConfigTest extends ApiTestCase
{
    public function test_payment_uses_database_gateway_credentials(): void
    {
        $this->authenticate();

        PaymentGatewayConfig::factory()->create([
            'name' => 'credit_card',
            'config' => ['api_key' => 'db_runtime_api_key_99'],
        ]);

        // Re-resolve manager so it picks up DB config for this request
        app()->forgetInstance(\App\Payments\PaymentGatewayManager::class);

        $order = Order::factory()->confirmed()->create(['total' => 200.00]);

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
        ]);

        $payment = $order->payments()->first();
        $this->assertStringContainsString('db_r', $payment->gateway_response['api_key_used']);
    }
}
