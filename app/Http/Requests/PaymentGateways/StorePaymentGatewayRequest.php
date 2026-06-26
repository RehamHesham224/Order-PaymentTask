<?php

namespace App\Http\Requests\PaymentGateways;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:payment_gateways,name'],
            'driver_class' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'config' => ['sometimes', 'array'],
            'config.api_key' => ['sometimes', 'string'],
            'config.secret' => ['sometimes', 'string'],
            'config.client_id' => ['sometimes', 'string'],
            'config.client_secret' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A payment gateway with this name already exists.',
            'driver_class.required' => 'The gateway driver class is required.',
        ];
    }
}
