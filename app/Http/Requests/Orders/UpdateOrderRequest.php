<?php

namespace App\Http\Requests\Orders;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'string', 'email', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(OrderStatus::values())],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'customer name',
            'customer_email' => 'customer email',
            'status' => 'order status',

            'items' => 'order items',
            'items.*.product_name' => 'product name',
            'items.*.quantity' => 'quantity',
            'items.*.price' => 'price',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The selected order status is invalid.',
            'items.min' => 'At least one item is required.',
            'items.array' => 'Order items must be provided as an array.',
        ];
    }
}
