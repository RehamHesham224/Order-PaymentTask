<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Payments\AbstractGateway;
use App\Payments\DTOs\PaymentResult;
use Illuminate\Support\Str;

class CreditCardGateway extends AbstractGateway
{
    public function getName(): string
    {
        return 'credit_card';
    }

    public function supports(string $paymentMethod): bool
    {
        return $paymentMethod === PaymentMethod::CreditCard->value;
    }

    public function process(Order $order, array $payload): PaymentResult
    {
        $apiKey = $this->config('api_key');
        $cardNumber = $payload['card_number'] ?? '';

        $lastFour = Str::substr(preg_replace('/\D/', '', $cardNumber), -4);

        if (Str::endsWith($cardNumber, '0000')) {
            return new PaymentResult(
                status: PaymentStatus::Failed,
                transactionReference: 'cc_'.Str::uuid(),
                gatewayResponse: [
                    'gateway' => $this->getName(),
                    'api_key_used' => Str::mask($apiKey ?? '', '*', 4),
                    'last_four' => $lastFour,
                    'decline_code' => 'card_declined',
                ],
                failureReason: 'Card was declined by the issuer.',
            );
        }

        return new PaymentResult(
            status: PaymentStatus::Successful,
            transactionReference: 'cc_'.Str::uuid(),
            gatewayResponse: [
                'gateway' => $this->getName(),
                'api_key_used' => Str::mask($apiKey ?? '', '*', 4),
                'last_four' => $lastFour,
                'authorization_code' => strtoupper(Str::random(6)),
            ],
        );
    }
}
