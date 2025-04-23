<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "account_id",
        "type",
        "name",
    ];

    public function scopeSearch($query, $search)
    {
        $query->where("name", "%{$search}%");
    }

    public function scopeExists($query, $search)
    {
        $query->where('user_id', $search["user_id"])->where('account_id', $search["account_id"])->where('type', $search["type"])->where("name", $search["name"]);
    }
}
