<?php

namespace App\Payments;

use App\Models\PaymentGatewayConfig;
use Illuminate\Support\Facades\Schema;

class PaymentGatewayConfigRepository
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $fileGateways = config('payment.gateways', []);

        if (! $this->canLoadFromDatabase()) {
            return $fileGateways;
        }

        $dbRecords = PaymentGatewayConfig::query()->get()->keyBy('name');

        $merged = [];

        foreach ($fileGateways as $name => $fileConfig) {
            $dbRecord = $dbRecords->get($name);

            if ($dbRecord && ! $dbRecord->is_active) {
                continue;
            }

            $merged[$name] = $dbRecord
                ? $this->mergeConfig($fileConfig, $dbRecord)
                : $fileConfig;
        }

        foreach ($dbRecords as $name => $dbRecord) {
            if ($dbRecord->is_active && ! isset($merged[$name])) {
                $merged[$name] = array_merge(
                    ['driver' => $dbRecord->driver_class],
                    $dbRecord->config ?? [],
                );
            }
        }

        return $merged;
    }

    private function mergeConfig(array $fileConfig, PaymentGatewayConfig $dbRecord): array
    {
        return array_merge(
            $fileConfig,
            $dbRecord->config ?? [],
            ['driver' => $dbRecord->driver_class ?: ($fileConfig['driver'] ?? null)],
        );
    }

    private function canLoadFromDatabase(): bool
    {
        try {
            return Schema::hasTable('payment_gateways');
        } catch (\Throwable) {
            return false;
        }
    }
}
