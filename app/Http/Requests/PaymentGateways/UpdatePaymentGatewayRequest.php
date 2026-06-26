<?php

namespace App\Http\Requests\PaymentGateways;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $gatewayId = $this->route('payment_gateway');

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('payment_gateways', 'name')->ignore($gatewayId)],
            'driver_class' => ['sometimes', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'config.api_key' => ['sometimes', 'string'],
            'config.secret' => ['sometimes', 'string'],
            'config.client_id' => ['sometimes', 'string'],
            'config.client_secret' => ['sometimes', 'string'],
        ];
    }
}
