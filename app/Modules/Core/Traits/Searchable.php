<?php

namespace App\Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearch(Builder $query, string $search, ?array $columns = null): Builder
    {
        $columns = $columns ?? $this->getSearchableColumns();

        if (empty($columns) || empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($columns, $search) {
            foreach ($columns as $column) {
                if (str_contains($column, '.')) {
                    [$relation, $relationColumn] = explode('.', $column, 2);
                    $query->orWhereHas($relation, function (Builder $q) use ($relationColumn, $search) {
                        $q->where($relationColumn, 'LIKE', "%{$search}%");
                    });
                } else {
                    $query->orWhere($column, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    public function getSearchableColumns(): array
    {
        return property_exists($this, 'searchable') ? $this->searchable : [];
    }
}
