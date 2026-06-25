<?php

namespace App\Filters\Orders;

use App\Support\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusFilter extends Filter
{
    protected function apply(Builder $query): void
    {
        if ($this->filled('status')) {
            $query->where('status', $this->input('status'));
        }
    }
}
