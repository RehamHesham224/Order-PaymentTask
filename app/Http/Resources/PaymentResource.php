<?php

namespace App\Http\Resources;

use App\Models\Payment;
use Illuminate\Http\Request;

/** @mixin Payment */
class PaymentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'order_id' => $this->order_id,
            'status' => $this->status->value,
            'payment_method' => $this->payment_method,
            'gateway' => $this->gateway,
            'amount' => $this->amount,
            'gateway_response' => $this->gateway_response,
            'order' => new OrderResource($this->whenLoaded('order')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
