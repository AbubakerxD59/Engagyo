<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class FacebookTestCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_type',
        'status',
        'failure_reason',
        'test_post_id',
        'facebook_page_id',
        'test_data',
        'ran_at',
    ];

    protected $casts = [
        'test_data' => 'array',
        'ran_at' => 'datetime',
    ];

    public function testPost()
    {
        return $this->belongsTo(Post::class, 'test_post_id');
    }

    public function facebookPage()
    {
        return $this->belongsTo(Page::class, 'facebook_page_id');
    }

    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('test_type', $type);
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::make(
            get: function () {
                $badges = [
                    'passed' => '<span class="badge badge-success">Passed</span>',
                    'failed' => '<span class="badge badge-danger">Failed</span>',
                    'pending' => '<span class="badge badge-warning">Pending</span>',
                ];

                return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
            }
        );
    }
}
