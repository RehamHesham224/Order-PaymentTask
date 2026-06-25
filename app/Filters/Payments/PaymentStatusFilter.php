<?php

namespace App\Filters\Payments;

use App\Support\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PaymentStatusFilter extends Filter
{
    protected function apply(Builder $query): void
    {
        if ($this->filled('status')) {
            $query->where('status', $this->input('status'));
        }
    }
}
