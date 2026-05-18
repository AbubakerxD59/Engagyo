<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_account_id',
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

    public function instagramAccount()
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
