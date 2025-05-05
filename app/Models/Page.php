<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "fb_id",
        "page_id",
        "name",
        "status",
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function facebook()
    {
        return $this->belongsTo(Facebook::class, 'fb_id', 'fb_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'account_id', 'fb_id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'account_id', 'fb_id');
    }

    public function scopeConnected($query, $search)
    {
        $query->where('user_id', $search['user_id'])->where('fb_id', $search['fb_id'])->where('page_id', $search['page_id']);
    }

    public function scopeSearch($query, $search)
    {
        $query->where('pin_id', $search)->orWhere('board_id', $search)->orWhere('name', $search);
    }

    public function scopeUserSearch($query, $id)
    {
        $query->where('user_id', $id);
    }
}
