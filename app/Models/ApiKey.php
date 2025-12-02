<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'secret',
        'last_used_at',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key.
     *
     * @return string
     */
    public static function generateKey(): string
    {
        return 'engagyo_' . Str::random(48);
    }

    /**
     * Generate a new API secret.
     *
     * @return string
     */
    public static function generateSecret(): string
    {
        return hash('sha256', Str::random(64));
    }

    /**
     * Refresh the API key.
     *
     * @return string The new key
     */
    public function refresh(): string
    {
        $newKey = self::generateKey();
        $this->update([
            'key' => $newKey,
            'secret' => self::generateSecret(),
        ]);
        return $newKey;
    }

    /**
     * Mark the API key as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope a query to only include active API keys.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find an API key by its key string.
     *
     * @param string $key
     * @return static|null
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->active()->first();
    }
}

