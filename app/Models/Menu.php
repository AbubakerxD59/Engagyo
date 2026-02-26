<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = "menus";
    protected $fillable = [
        "name",
        "icon",
        "route",
        "display_order"
    ];

    /**
     * Additional menus not displayed in sidebar (e.g. navbar-only items like API Keys).
     * These are used for team member access control.
     */
    public static function additionalMenus(): array
    {
        return [
            [
                'id' => 'api',
                'name' => 'API Access',
                'icon' => 'fas fa-key',
                'route' => 'panel.api-keys',
            ],
        ];
    }

    public function features()
    {
        return $this->hasMany(Feature::class, 'parent_id', 'id');
    }
}
