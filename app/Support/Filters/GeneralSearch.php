<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;

class GeneralSearch extends Filter
{
    protected function apply(Builder $query): void
    {
        if (! $this->filled('search')) {
            return;
        }

        $model = $query->getModel();

        if (! method_exists($model, 'searchableColumns')) {
            return;
        }

        $search = $this->input('search');

        $query->where(function (Builder $builder) use ($search, $model) {
            foreach ($model->searchableColumns() as $column) {
                $builder->orWhere($column, 'like', "%{$search}%");
            }
        });
    }
}
