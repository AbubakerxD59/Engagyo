<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facebook extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "fb_id",
        "username",
        "profile_image",
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
