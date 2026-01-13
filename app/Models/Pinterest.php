<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pinterest extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "pin_id",
        "username",
        "about",
        "profile_image",
        "board_count",
        "pin_count",
        "following_count",
        "follower_count",
        "monthly_views",
        "access_token",
        "expires_in",
        "refresh_token",
        "refresh_token_expires_in",
    ];

    protected $appends = ["type"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function boards()
    {
        return $this->hasMany(Board::class, 'pin_id',  'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where('pin_id', $search)->orWhere("username", "%{$search}%");
    }

    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $now = strtotime(date("Y-m-d H:i:s"));
                $expires_in = $now + $value;
                return date("Y-m-d H:i:s", $expires_in);
            },
            get: function ($value) {
                $expires_in = strtotime($value);
                return $expires_in;
            }
        );
    }

    protected function refreshTokenExpiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $now = strtotime(date("Y-m-d H:i:s"));
                $refresh_token_expires_in = $now + $value;
                return date("Y-m-d H:i:s", $refresh_token_expires_in);
            },
            get: function ($value) {
                $refresh_token_expires_in = strtotime($value);
                return $refresh_token_expires_in;
            }
        );
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => asset("images/" . $value)
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "pinterest";
            }
        );
    }

    public function validToken()
    {
        $now = strtotime(date("Y-m-d H:i:s"));
        $expires_in = $this->expires_in;
        return $expires_in >= $now ? true : false;
    }

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);
    }
}
