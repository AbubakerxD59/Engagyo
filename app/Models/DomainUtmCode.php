<?php

namespace App\Models;

use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainUtmCode extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "domain_name",
        "utm_key",
        "utm_value"
    ];


    public static $utm_keys = [
        "utm_campaign" => 'Campaign',
        "utm_medium" => 'Medium',
        "utm_source" => 'Source',
    ];

    public static $utm_values = [
        "social_type" =>"Social Type",
        "social_profile" => "Social Profile",
        "custom" => "Custom"
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Scope to filter by user ID
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by domain name (case-insensitive)
     */
    public function scopeForDomainName($query, $domainName)
    {
        return $query->whereRaw('LOWER(domain_name) = ?', [strtolower($domainName)]);
    }

    /**
     * Scope to get UTM codes for a user and domain name
     */
    public function scopeForUserAndDomain($query, $userId, $domainName)
    {
        return $query->forUser($userId)->forDomainName($domainName);
    }

    protected static function booted()
    {
        static::addGlobalScope(new UserScope);
    }
}
