<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Model for historical/archived feature usage data
 * This uses the same table as UserFeatureUsage but with is_archived = true
 */
class UserFeatureUsageHistory extends Model
{
    use HasFactory;

    protected $table = 'user_feature_usages';

    protected $fillable = [
        'user_id',
        'feature_id',
        'usage_count',
        'is_unlimited',
        'period_start',
        'period_end',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'is_unlimited' => 'boolean',
        'is_archived' => 'boolean',
        'period_start' => 'date',
        'period_end' => 'date',
        'archived_at' => 'datetime',
    ];

    /**
     * Boot the model and set default scope to only archived records
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('archived', function ($builder) {
            $builder->where('is_archived', true);
        });
    }

    /**
     * Get the user that owns this historical usage record
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the feature for this historical usage record
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Scope to get usage for a specific month
     */
    public function scopeForMonth($query, $year, $month)
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        return $query->where('period_start', $periodStart);
    }

    /**
     * Scope to get usage for a specific year
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('period_start', $year);
    }

    /**
     * Scope to get usage for a date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('period_start', [$startDate, $endDate]);
    }
}

