<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'icon',
        'duration',
        'date_type',
        'price',
        'stripe_product_id',
        'stripe_price_id',
    ];

    protected $appends = ['date_duration'];

    public function scopeSearch($query, $search)
    {
        $query->where('name', 'like', "%{$search}%");
    }

    public function time_duration()
    {
        return $this->duration . ' ' . $this->date_type;
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value != '' && $value != null) ? url(getImage('', $value)) : '',
        );
    }

    protected function dateDuration(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $this->duration . ' ' . $this->date_type,
        );
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'feature_packages');
    }

    public function getFeatures()
    {
        return $this->features()->pluck('name')->toArray();
    }

    public function checkFeatures()
    {
        $currentFeatures = $this->getFeatures();
        $features = check_features($currentFeatures, $this->id);
        return $features;
    }
}
