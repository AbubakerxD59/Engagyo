<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserFeatureUsage extends Model
{
    use HasFactory;
    
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
     * Get the user that owns this usage record
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the feature for this usage record
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    /**
     * Scope to get current period usage (not archived)
     */
    public function scopeCurrentPeriod($query)
    {
        return $query->where('is_archived', false)
            ->where('period_start', '>=', now()->startOfMonth());
    }

    /**
     * Scope to get archived usage records
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to get usage for a specific period
     */
    public function scopeForPeriod($query, $periodStart)
    {
        return $query->where('period_start', $periodStart);
    }

    /**
     * Check if this usage record is for the current month
     */
    public function isCurrentPeriod()
    {
        if ($this->is_archived || !$this->period_start) {
            return false;
        }
        
        $periodStart = $this->period_start instanceof \Carbon\Carbon 
            ? $this->period_start 
            : \Carbon\Carbon::parse($this->period_start);
            
        return $periodStart->format('Y-m') === now()->format('Y-m');
    }

    /**
     * Archive this usage record
     */
    public function archive()
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'period_end' => $this->period_end ?? now()->endOfMonth(),
        ]);
    }
}

