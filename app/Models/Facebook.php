<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Facebook extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "fb_id",
        "username",
        "profile_image",
        "access_token",
    ];

    public function pages()
    {
        return $this->hasMany(Page::class, 'fb_id', 'fb_id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where('fb_id', $search)->orWhere("username", "%{$search}%");
    }

    public function scopeUser($query, $id)
    {
        $query->where('user_id', $id);
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => !empty($value) ? asset("images/" . $value) : no_image()
        );
    }

    public function validToken()
    {
       return true;
    }
}
