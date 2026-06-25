<?php

namespace App\Support\Filters;

use App\Support\Contracts\Filters\FilterContract;
use Closure;
use Illuminate\Database\Eloquent\Builder;

abstract class Filter implements FilterContract
{
    abstract protected function apply(Builder $query): void;

    public function handle(Builder $query, Closure $next): mixed
    {
        $this->apply($query);

        return $next($query);
    }

    protected function filled(string $key): bool
    {
        return request()->filled($key);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return request($key, $default);
    }
}
