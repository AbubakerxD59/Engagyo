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
        "time"
    ];

    protected $casts = [
        "time" => "array"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class, 'account_id', 'board_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class, 'account_id', 'page_id');
    }

    public function posts(){
        return $this->hasMany(Post::class, 'domain_id', 'id');
    }

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

    public function scopeUserSearch($query, $id)
    {
        $query->where("user_id", $id);
    }

    public function scopeAccounts($query, $id)
    {
        $query->whereIn("account_id", $id);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $name = empty($this->category) ? $value : $value . $this->category;
                return "https://" . $name;
            }
        );
    }
}
