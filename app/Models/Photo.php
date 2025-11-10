<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;
    protected $table = 'photos';
    protected $fillable = [
        "post_id",
        "mode",
        "url",
        "status",
        "response",
    ];
    public function post(){
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
}
