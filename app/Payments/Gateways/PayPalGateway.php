<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'paypal';
    }

    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === PaymentMethod::PayPal->value;
    }

    public function process(Order $order, array $payload): PaymentResult
    {
        $clientId = config('payment.gateways.paypal.client_id');
        $clientSecret = config('payment.gateways.paypal.client_secret');
        $email = $payload['paypal_email'] ?? '';

        if (Str::contains($email, 'fail')) {
            return new PaymentResult(
                status: PaymentStatus::Failed,
                transactionReference: 'pp_'.Str::uuid(),
                gatewayResponse: [
                    'gateway' => $this->getName(),
                    'client_id' => Str::mask($clientId ?? '', '*', 4),
                    'paypal_email' => $email,
                    'error' => 'PAYMENT_DENIED',
                ],
                failureReason: 'PayPal payment was denied.',
            );
        }

        return new PaymentResult(
            status: PaymentStatus::Successful,
            transactionReference: 'pp_'.Str::uuid(),
            gatewayResponse: [
                'gateway' => $this->getName(),
                'client_id' => Str::mask($clientId ?? '', '*', 4),
                'paypal_email' => $email,
                'capture_id' => strtoupper(Str::random(17)),
            ],
        );
    }
}
