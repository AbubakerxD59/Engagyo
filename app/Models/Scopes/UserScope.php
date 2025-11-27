<?php

namespace App\Models\Scopes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class UserScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tableName = $model->getTable();
        if (Schema::hasColumn($tableName, 'user_id')) {
            if (Auth::check()) {
                $builder->where($tableName . '.user_id', Auth::id());
            }
        }
    }
}
