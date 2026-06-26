<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config = []) {}

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
