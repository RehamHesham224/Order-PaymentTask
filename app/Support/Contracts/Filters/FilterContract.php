<?php

namespace App\Support\Contracts\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;

interface FilterContract
{
    public function handle(Builder $query, Closure $next): mixed;
}
