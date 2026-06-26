<?php

namespace App\Services;

use App\Models\PaymentGatewayConfig;
use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PaymentGatewayConfigService
{
    public function list(): Collection
    {
        return PaymentGatewayConfig::query()->orderBy('name')->get();
    }

    public function find(int $id): PaymentGatewayConfig
    {
        return PaymentGatewayConfig::findOrFail($id);
    }

    public function create(array $data): PaymentGatewayConfig
    {
        $this->validateDriverClass($data['driver_class']);

        return PaymentGatewayConfig::create($data);
    }

    public function update(PaymentGatewayConfig $gateway, array $data): PaymentGatewayConfig
    {
        if (isset($data['driver_class'])) {
            $this->validateDriverClass($data['driver_class']);
        }

        $gateway->update($data);

        return $gateway->fresh();
    }

    public function delete(PaymentGatewayConfig $gateway): void
    {
        $gateway->delete();
    }

    private function validateDriverClass(string $driverClass): void
    {
        if (! class_exists($driverClass)) {
            throw ValidationException::withMessages([
                'driver_class' => ['The driver class does not exist.'],
            ]);
        }

        if (! is_subclass_of($driverClass, PaymentGatewayInterface::class)) {
            throw ValidationException::withMessages([
                'driver_class' => ['The driver class must implement PaymentGatewayInterface.'],
            ]);
        }
    }
}
