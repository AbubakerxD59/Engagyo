<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PagePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
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

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
