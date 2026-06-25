<?php

namespace App\Support\Traits;

use App\Support\Contracts\Filters\FilterContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pipeline\Pipeline;

trait Filterable
{
    /**
     * @param  array<class-string<FilterContract>>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return app(Pipeline::class)
            ->send($query)
            ->through($filters)
            ->thenReturn();
    }
}
