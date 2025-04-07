<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'name'
    ];

    public function scopeSearch($query, $search)
    {
        $query->where('name', 'like', "%{$search}%");
    }
}
