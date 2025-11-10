<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class Photo extends Model
{
    use HasFactory;
    protected $table = 'photos';
    protected $fillable = [
        "post_id",
        "mode",
        "url",
        "tries",
        "status",
        "response",
    ];
    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }
    public function scopePending($query)
    {
        $query->where("status", 'pending');
    }
    public function scopeAvailable($query, $max_tries)
    {
        $query->where("tries", '<=', $max_tries);
    }
    public function scopePast($query)
    {
        $query->whereHas("post", function ($q) {
            $q->where("publish_date", "<=", date("Y-m-d", strtotime("+2 days")));
        });
    }
}
