<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YoutubeInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'youtube_id',
        'duration',
        'since',
        'until',
        'insights',
        'synced_at',
    ];

    protected $casts = [
        'insights' => 'array',
        'since' => 'date',
        'until' => 'date',
        'synced_at' => 'datetime',
    ];

    public function youtube()
    {
        return $this->belongsTo(Youtube::class);
    }
}
