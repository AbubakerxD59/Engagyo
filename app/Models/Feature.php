<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'key',
        'name',
        'type',
        'default_value',
        'description',
        'is_active'
    ];

    public static $features_list = [
        "0" => "social_accounts",
        "1" => "scheduled_posts_per_account",
        "2" => "rss_feed_automation",
        "5" => "api_access",
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'default_value' => 'integer',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'parent_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('key', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
