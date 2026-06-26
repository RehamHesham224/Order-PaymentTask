<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function make(string $name, array $config): PaymentGatewayInterface
    {
        $driver = $config['driver'] ?? null;

        if (! $driver || ! class_exists($driver)) {
            throw new InvalidArgumentException(
                "Payment gateway [{$name}] has an invalid driver [{$driver}]."
            );
        }

        if (! is_subclass_of($driver, PaymentGatewayInterface::class)) {
            throw new InvalidArgumentException(
                "Payment gateway driver [{$driver}] must implement PaymentGatewayInterface."
            );
        }

        return new $driver($config);
    }
}
