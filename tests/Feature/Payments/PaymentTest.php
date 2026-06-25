<?php

namespace Tests\Feature\Payments;

use App\Models\Order;
use App\Models\Payment;
use Tests\ApiTestCase;

class PaymentTest extends ApiTestCase
{
    public function test_can_process_credit_card_payment_for_confirmed_order(): void
    {
        $this->authenticate();

        $order = Order::factory()->confirmed()->create(['total' => 150.00]);

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'payment_id' => 'pay_test_001',
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Data created successfully.');

        $this->assertDatabaseHas('payments', [
            'payment_id' => 'pay_test_001',
            'order_id' => $order->id,
            'status' => 'successful',
            'gateway' => 'credit_card',
            'amount' => 150.00,
        ]);
    }

    public function test_credit_card_ending_in_0000_fails(): void
    {
        $this->authenticate();

        $order = Order::factory()->confirmed()->create();

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'payment_method' => 'credit_card',
            'card_number' => '4111111111110000',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $this->authenticate();

        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'payment_method' => 'credit_card',
            'card_number' => '4111111111111111',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_can_process_paypal_payment(): void
    {
        $this->authenticate();

        $order = Order::factory()->confirmed()->create();

        $response = $this->postJson("/api/orders/{$order->id}/payments", [
            'payment_method' => 'paypal',
            'paypal_email' => 'buyer@example.com',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'successful',
            'gateway' => 'paypal',
        ]);
    }

    public function test_can_list_payments_for_order(): void
    {
        $this->authenticate();

        $order = Order::factory()->confirmed()->create();
        Payment::factory()->count(2)->create(['order_id' => $order->id]);
        Payment::factory()->create();

        $response = $this->getJson("/api/payments?order_id={$order->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'body.payments.data');
    }

    public function test_can_show_payment(): void
    {
        $this->authenticate();

        $payment = Payment::factory()->create(['payment_id' => 'pay_show_001']);

        $response = $this->getJson("/api/payments/{$payment->id}");

        $response->assertOk()
            ->assertJsonPath('body.payment.payment_id', 'pay_show_001');
    }
}
