<?php

namespace App\Models\Scopes;

use App\Models\Board;
use App\Models\Page;
use App\Models\Tiktok;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class TeamScope implements Scope
{
    /**
     * Map model class to account_type in team_member_accounts
     */
    private const ACCOUNT_TYPE_MAP = [
        Page::class => 'page',
        Board::class => 'board',
        Tiktok::class => 'tiktok',
    ];

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tableName = $model->getTable();
        if (!Schema::hasColumn($tableName, 'user_id')) {
            return;
        }

        if (!Auth::check()) {
            return;
        }

        $admin = Auth::guard('admin')->user();
        if ($admin) {
            return;
        }

        $user = Auth::guard('user')->user();
        if (!$user instanceof User) {
            return;
        }

        if ($user->isTeamMember()) {
            // Team member: only show accounts they have access to, filtered by account type
            $teamLead = $user->getTeamLead();
            if (!$teamLead) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $accountType = self::ACCOUNT_TYPE_MAP[get_class($model)] ?? null;
            if (!$accountType) {
                $builder->where('user_id', $teamLead->id);
                return;
            }

            $accountIds = $user->getTeamMemberAccountIdsByType($accountType);
            if (empty($accountIds)) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->where('user_id', $teamLead->id)
                ->whereIn($tableName . '.id', $accountIds);
        } else {
            // Team lead: show all their own accounts
            $builder->where('user_id', $user->id);
        }
    }
}
