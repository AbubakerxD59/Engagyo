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
        'is_enabled',
        'is_unlimited'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_unlimited' => 'boolean',
        'limit_value' => 'integer',
    ];
}
