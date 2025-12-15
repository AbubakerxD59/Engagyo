<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'type',
        'default_value',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_value' => 'integer',
    ];

    public function scopeSearch($query, $search)
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('key', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
