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
        "display_order"
    ];
    public function features(){
        return $this->hasMany(Feature::class, 'parent_id', 'id');
    }
}
