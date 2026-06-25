<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $data, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($data, $userId) {
            $order = Order::create([
                'user_id' => $userId,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'status' => OrderStatus::Pending,
                'total' => 0,
            ]);

            $this->syncItems($order, $data['items']);
            $order->recalculateTotal();

            return $order->load('items');
        });
    }

    public function update(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'customer_name' => $data['customer_name'] ?? $order->customer_name,
                'customer_email' => $data['customer_email'] ?? $order->customer_email,
                'status' => $data['status'] ?? $order->status->value,
            ]);

            if (isset($data['items'])) {
                $order->items()->delete();
                $this->syncItems($order, $data['items']);
                $order->recalculateTotal();
            }

            return $order->fresh(['items', 'payments']);
        });
    }

    public function delete(Order $order): void
    {
        if ($order->hasPayments()) {
            throw ValidationException::withMessages([
                'order' => ['Orders with associated payments cannot be deleted.'],
            ]);
        }

        $order->delete();
    }

    private function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $subtotal = bcmul((string) $item['price'], (string) $item['quantity'], 2);

            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal,
            ]);
        }
    }
}
