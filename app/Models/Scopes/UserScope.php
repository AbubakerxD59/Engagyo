<?php

namespace App\Models\Scopes;

use App\Models\User;
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
                $user = Auth::guard("user")->user();
                // Only apply scope if user is a User instance and has "User" role (not admin/super admin)
                if ($user instanceof User) {
                    $role = $user->roles()->first();
                    if ($role && $role->name === 'User') {
                        $builder->where($tableName . '.user_id', Auth::id());
                    }
                }
            }
        }
    }
}
