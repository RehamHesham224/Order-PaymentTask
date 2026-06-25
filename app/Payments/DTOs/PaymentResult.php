<?php

namespace App\Payments\DTOs;

use App\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(
        public PaymentStatus $status,
        public string $transactionReference,
        public array $gatewayResponse = [],
        public ?string $failureReason = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::Successful;
    }
}
