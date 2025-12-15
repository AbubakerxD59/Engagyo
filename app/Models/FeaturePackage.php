<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'feature_id',
        'limit_value',
        'is_enabled'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limit_value' => 'integer',
    ];
}
