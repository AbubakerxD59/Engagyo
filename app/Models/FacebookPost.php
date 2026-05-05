<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacebookPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'fb_page_id',
        'fb_post_id',
        'permalink_url',
        'status_type',
        'post_type',
        'shares_count',
        'comments_count',
        'clicks_count',
        'reactions_count',
        'impressions_count',
        'reach_count',
        'engagement_rate',
        'post_data',
        'post_created_date',
        'post_insights',
        'fetched_at',
    ];

    protected $casts = [
        'post_data' => 'array',
        'post_created_date' => 'datetime',
        'post_insights' => 'array',
        'engagement_rate' => 'decimal:2',
        'fetched_at' => 'datetime',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class, 'fb_page_id', 'page_id');
    }
}
