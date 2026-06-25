<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Payments\DTOs\PaymentResult;

interface PaymentGatewayInterface
{
    public function getName(): string;

    public function supports(string $paymentMethod): bool;

    public function process(Order $order, array $payload): PaymentResult;
}
