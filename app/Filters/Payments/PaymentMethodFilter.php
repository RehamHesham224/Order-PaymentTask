<?php

namespace App\Filters\Payments;

use App\Support\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PaymentMethodFilter extends Filter
{
    protected function apply(Builder $query): void
    {
        if ($this->filled('payment_method')) {
            $query->where('payment_method', $this->input('payment_method'));
        }
    }
}
