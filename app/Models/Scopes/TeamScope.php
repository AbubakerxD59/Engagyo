<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class TeamScope implements Scope
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
                
                // If the user is an admin, return without applying scope
                if ($user instanceof User) {
                    $role = $user->roles()->first();
                    if ($role && $role->name !== 'User') {
                        return;
                    }
                }
                
                $builder->where("user_id", $user->id);
                // Logic for Team Members
                if ($user instanceof User) {
                    // If the user is a team member, also include accounts from their team lead
                    $account_ids = [];
                    if ($user->isTeamMember()) {
                        $account_ids = $user->getTeamMemberBoardIds();
                    }
                    $builder->orWhereIn($tableName . '.id', $account_ids);
                }
            }
        }
    }
}
