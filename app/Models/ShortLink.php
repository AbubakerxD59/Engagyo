<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShortLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'short_code',
        'shrtlnk_id',
        'short_url',
        'original_url',
        'user_agent',
        'ip_address',
        'clicks',
    ];

    protected $casts = [
        'clicks' => 'integer',
        'shrtlnk_id' => 'integer',
    ];

    /**
     * Public shareable URL (ShrtLnk when integrated, otherwise Engagyo /s/{code}).
     */
    public function publicShortUrl(): string
    {
        if (! empty($this->short_url)) {
            return $this->short_url;
        }

        if ($this->shrtlnk_id) {
            return rtrim((string) config('shrtlnk.base_url'), '/').'/s/'.$this->short_code;
        }

        return url('/s/'.$this->short_code);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Generate a unique short code.
     */
    public static function generateUniqueCode(int $length = 6): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (self::where('short_code', $code)->exists());

        return $code;
    }

    /**
     * Normalize URL for consistent comparison (scheme + host lowercase, trim, trailing slash).
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return $url;
        }
        $scheme = strtolower($parsed['scheme'] ?? 'https');
        $host = strtolower($parsed['host']);
        $path = $parsed['path'] ?? '/';
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        $query = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) && $parsed['fragment'] !== '' ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $host . $path . $query . $fragment;
    }
}
