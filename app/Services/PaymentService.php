<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {}

    public function process(Order $order, array $data): Payment
    {
        if (! $order->isConfirmed()) {
            throw ValidationException::withMessages([
                'order_id' => ['Payments can only be processed for confirmed orders.'],
            ]);
        }

        $gateway = $this->gatewayManager->resolve($data['payment_method']);

        return DB::transaction(function () use ($order, $data, $gateway) {
            $result = $gateway->process($order, $data);

            return Payment::create([
                'payment_id' => $data['payment_id'] ?? Str::uuid()->toString(),
                'order_id' => $order->id,
                'status' => $result->status,
                'payment_method' => $data['payment_method'],
                'gateway' => $gateway->getName(),
                'amount' => $order->total,
                'gateway_response' => array_merge($result->gatewayResponse, [
                    'failure_reason' => $result->failureReason,
                ]),
            ]);
        });
    }
}
