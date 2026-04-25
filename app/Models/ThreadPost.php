<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreadPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'thread_id',
        'duration',
        'since',
        'until',
        'posts',
        'synced_at',
    ];

    protected $casts = [
        'posts' => 'array',
        'since' => 'date',
        'until' => 'date',
        'synced_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }
}
