<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "account_id",
        "type",
        "name",
        "category",
    ];

    public function scopeSearch($query, $search)
    {
        $query->where("name", "%{$search}%");
    }

    public function scopeExists($query, $search)
    {
        $query->where('user_id', $search["user_id"])
            ->where('account_id', $search["account_id"])
            ->where('type', $search["type"])
            ->where("name", $search["name"]);
        if (!empty($search['category'])) {
            $query->where("category", $search['category']);
        }
    }

    public function scopeAccount($query, $id)
    {
        $query->where("account_id", $id);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $name = empty($this->category) ? $value : $value . $this->category;
                return "http://" . $name;
            }
        );
    }
}
