<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BaseModel
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'order_id',
        'status',
        'payment_method',
        'gateway',
        'amount',
        'gateway_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'gateway_response' => 'array',
        ];
    }

    /** @return array<int, string> */
    public function searchableColumns(): array
    {
        return ['payment_id', 'payment_method', 'gateway'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
