<?php

namespace App\Filters\Payments;

use App\Support\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class OrderIdFilter extends Filter
{
    protected function apply(Builder $query): void
    {
        if ($this->filled('order_id')) {
            $query->where('order_id', $this->input('order_id'));
        }
    }
}
