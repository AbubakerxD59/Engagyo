<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'name', 'guard_name', 'label', 'parent_id', 'show_on_menu', 'route_name', 'icon', 'tool_tip', 'sort_order', 'role_id', 'created_by',
    ];

    public function scopeSearch($query, $value)
    {
        $query->where('name', 'like', "%{$value}%");
    }
}
