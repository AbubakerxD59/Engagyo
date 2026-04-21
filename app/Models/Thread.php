<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Thread extends Model
{
    use HasFactory;

    protected $table = 'threads';

    protected $fillable = [
        'user_id',
        'threads_id',
        'username',
        'profile_image',
        'access_token',
        'expires_in',
        'refresh_token',
    ];

    protected $appends = ['type'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeSearch($query, $search)
    {
        $query->where('threads_id', $search)->orWhere('username', 'like', "%{$search}%");
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => !empty($value) ? asset('images/' . $value) : no_image()
        );
    }

    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_numeric($value)) {
                    $now = strtotime(date('Y-m-d H:i:s'));
                    $expiresIn = $now + (int) $value;
                    return date('Y-m-d H:i:s', $expiresIn);
                }

                return $value;
            },
            get: function ($value) {
                if (empty($value)) {
                    return 0;
                }

                return strtotime($value);
            }
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn() => 'threads'
        );
    }

    public function validToken(): bool
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        return $this->expires_in >= $now;
    }

    public function getTimeslotsAttribute()
    {
        return collect();
    }

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope());
    }
}
