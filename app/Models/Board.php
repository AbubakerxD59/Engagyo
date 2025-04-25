<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "pin_id",
        "board_id",
        "name",
        "status",
    ];

    protected $appends = ["type"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pinterest()
    {
        return $this->belongsTo(Pinterest::class, 'pin_id', 'pin_id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where('pin_id', $search)->orWhere('board_id', $search)->orWhere('name', $search);
    }

    public function scopeUserSearch($query, $id)
    {
        $query->where('user_id', $id);
    }

    public function scopeActive($query)
    {
        $query->where('status', '1');
    }

    public function scopeConnected($query, $search)
    {
        $query->where('user_id', $search['user_id'])->where('pin_id', $search['pin_id'])->where('board_id', $search['board_id']);
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "pinterest";
            }
        );
    }

    public function getPinterest($pin_id)
    {
        $pinterest = $this->pinterest()->first();
        dd($pinterest, $pin_id);
        return $pinterest;
    }
}
