<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TikTokTestCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_type',
        'status',
        'failure_reason',
        'test_post_id',
        'tiktok_account_id',
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

    public function tiktokAccount()
    {
        return $this->belongsTo(Tiktok::class, 'tiktok_account_id');
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
                $badgeClass = '';
                $statusText = ucfirst($this->status);
                switch ($this->status) {
                    case 'passed':
                        $badgeClass = 'badge-success';
                        break;
                    case 'failed':
                        $badgeClass = 'badge-danger';
                        break;
                    case 'pending':
                        $badgeClass = 'badge-warning';
                        break;
                    default:
                        $badgeClass = 'badge-secondary';
                        break;
                }
                $html = "<span class='badge {$badgeClass}'>{$statusText}</span>";
                if ($this->status === 'failed' && $this->failure_reason) {
                    $html .= " <i class='fas fa-info-circle text-danger' data-toggle='tooltip' title='" . e($this->failure_reason) . "'></i>";
                }
                return $html;
            }
        );
    }
}

