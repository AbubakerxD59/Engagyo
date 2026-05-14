<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
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

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
