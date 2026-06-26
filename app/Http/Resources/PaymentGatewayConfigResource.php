<?php

namespace App\Http\Resources;

use App\Models\PaymentGatewayConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** @mixin PaymentGatewayConfig */
class PaymentGatewayConfigResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'driver_class' => $this->driver_class,
            'is_active' => $this->is_active,
            'config' => $this->maskSensitiveConfig($this->config ?? []),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** @param  array<string, mixed>  $config */
    private function maskSensitiveConfig(array $config): array
    {
        foreach (PaymentGatewayConfig::sensitiveConfigKeys() as $key) {
            if (isset($config[$key]) && is_string($config[$key])) {
                $config[$key] = Str::mask($config[$key], '*', 4);
            }
        }

        return $config;
    }
}
