<?php

namespace App\Models;

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
    ];

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => asset("images/".$value)
        );
    }
}
