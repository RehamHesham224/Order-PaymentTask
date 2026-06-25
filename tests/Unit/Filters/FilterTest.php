<?php

namespace Tests\Unit\Filters;

use App\Filters\Orders\OrderStatusFilter;
use App\Filters\Payments\OrderIdFilter;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Filters\GeneralSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_status_filter(): void
    {
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->confirmed()->create();

        request()->merge(['status' => 'confirmed']);

        $results = Order::filter([OrderStatusFilter::class])->get();

        $this->assertCount(1, $results);
        $this->assertSame('confirmed', $results->first()->status->value);
    }

    public function test_general_search_filter_on_orders(): void
    {
        Order::factory()->create(['customer_name' => 'Alice Johnson']);
        Order::factory()->create(['customer_name' => 'Bob Smith']);

        request()->merge(['search' => 'Alice']);

        $results = Order::filter([GeneralSearch::class])->get();

        $this->assertCount(1, $results);
        $this->assertSame('Alice Johnson', $results->first()->customer_name);
    }

    public function test_order_id_filter_on_payments(): void
    {
        $order = Order::factory()->confirmed()->create();
        Payment::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create();

        request()->merge(['order_id' => $order->id]);

        $results = Payment::filter([OrderIdFilter::class])->get();

        $this->assertCount(1, $results);
        $this->assertSame($order->id, $results->first()->order_id);
    }
}
