<?php

namespace App\Models;

use Database\Factories\PaymentGatewayConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentGatewayConfig extends BaseModel
{
    /** @use HasFactory<PaymentGatewayConfigFactory> */
    use HasFactory;

    protected $table = 'payment_gateways';

    protected $fillable = [
        'name',
        'driver_class',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    /** @return array<int, string> */
    public static function sensitiveConfigKeys(): array
    {
        return [
            'api_key',
            'secret',
            'client_id',
            'client_secret',
            'webhook_secret',
        ];
    }
}
