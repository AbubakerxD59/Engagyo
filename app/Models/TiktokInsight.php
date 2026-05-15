<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiktokInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'tiktok_id',
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

    public function tiktok()
    {
        return $this->belongsTo(Tiktok::class);
    }
}
