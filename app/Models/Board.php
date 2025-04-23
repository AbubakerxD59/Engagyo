<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
