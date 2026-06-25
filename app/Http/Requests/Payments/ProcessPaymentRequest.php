<?php

namespace App\Http\Requests\Payments;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['sometimes', 'string', 'max:255', 'unique:payments,payment_id'],
            'payment_method' => ['required', 'string', Rule::in(PaymentMethod::values())],
            'card_number' => ['required_if:payment_method,credit_card', 'string', 'min:13', 'max:19'],
            'paypal_email' => ['required_if:payment_method,paypal', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.unique' => 'This payment ID has already been used.',
            'payment_method.in' => 'The selected payment method is not supported.',
            'card_number.required_if' => 'Card number is required for credit card payments.',
            'paypal_email.required_if' => 'PayPal email is required for PayPal payments.',
        ];
    }
}
