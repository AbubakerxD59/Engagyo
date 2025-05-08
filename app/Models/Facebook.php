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
        "expires_in"
    ];

    protected $appends = ["type"];

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pages()
    {
        return $this->hasMany(Page::class, 'fb_id', 'fb_id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where('fb_id', $search)->orWhere("username", "%{$search}%");
    }

    public function scopeUserSearch($query, $id)
    {
        $query->where('user_id', $id);
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => !empty($value) ? asset("images/" . $value) : no_image()
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "facebook";
            }
        );
    }

    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $expires_in = date("Y-m-d H:i:s", $value);
                return $expires_in;
            },
            get: function ($value) {
                $expires_in = strtotime($value);
                return $expires_in;
            }
        );
    }

    public function validToken()
    {
        $now = strtotime(date("Y-m-d H:i:s"));
        $expires_in = $this->expires_in;
        return $now > $expires_in ? true : false;
    }
}
