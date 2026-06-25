<?php

namespace Tests\Feature\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use Tests\ApiTestCase;

class OrderTest extends ApiTestCase
{
    public function test_can_create_order_with_items_and_calculated_total(): void
    {
        $this->authenticate();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
            'items' => [
                ['product_name' => 'Widget', 'quantity' => 2, 'price' => 25.50],
                ['product_name' => 'Gadget', 'quantity' => 1, 'price' => 49.00],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Data created successfully.')
            ->assertJsonPath('status', false);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'John Smith',
            'total' => 100.00,
            'status' => 'pending',
        ]);
    }

    public function test_can_list_orders_filtered_by_status(): void
    {
        $this->authenticate();

        Order::factory()->create(['status' => OrderStatus::Pending]);
        Order::factory()->confirmed()->create();
        Order::factory()->cancelled()->create();

        $response = $this->getJson('/api/orders?status=confirmed');

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonCount(1, 'body.orders.data')
            ->assertJsonPath('body.orders.data.0.status', 'confirmed');
    }

    public function test_can_search_orders_by_customer_name(): void
    {
        $this->authenticate();

        Order::factory()->create(['customer_name' => 'Unique Customer']);
        Order::factory()->create(['customer_name' => 'Someone Else']);

        $response = $this->getJson('/api/orders?search=Unique');

        $response->assertOk()
            ->assertJsonCount(1, 'body.orders.data')
            ->assertJsonPath('body.orders.data.0.customer_name', 'Unique Customer');
    }

    public function test_can_show_order(): void
    {
        $this->authenticate();

        $order = Order::factory()->create(['customer_name' => 'John Smith']);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('body.order.customer_name', 'John Smith');
    }

    public function test_can_update_order(): void
    {
        $this->authenticate();

        $order = Order::factory()->create(['customer_name' => 'Old Name']);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'customer_name' => 'New Name',
            'status' => 'confirmed',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Data updated successfully.');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'New Name',
            'status' => 'confirmed',
        ]);
    }

    public function test_can_delete_order_without_payments(): void
    {
        $this->authenticate();

        $order = Order::factory()->create();

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Data deleted successfully.');
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_order_with_payments(): void
    {
        $this->authenticate();

        $order = Order::factory()->confirmed()->create();
        Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['order']);
    }
}
