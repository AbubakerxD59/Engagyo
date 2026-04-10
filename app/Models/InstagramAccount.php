<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InstagramAccount extends Model
{
    use HasFactory;

    protected $table = 'instagram_accounts';

    protected $fillable = [
        'user_id',
        'facebook_id',
        'page_id',
        'ig_user_id',
        'username',
        'name',
        'profile_image',
        'access_token',
        'expires_in',
    ];

    protected $appends = ['type', 'schedule_status'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function facebook()
    {
        return $this->belongsTo(Facebook::class, 'facebook_id', 'id');
    }

    /**
     * Facebook Page row (same Graph page_id as this Instagram Business link).
     */
    public function linkedPage()
    {
        return $this->belongsTo(Page::class, 'page_id', 'page_id')
            ->whereColumn('pages.user_id', 'instagram_accounts.user_id');
    }

    public function getTimeslotsAttribute()
    {
        $this->loadMissing('linkedPage.timeslots');

        return $this->linkedPage?->timeslots ?? collect();
    }

    protected function scheduleStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('linkedPage');

                return $this->linkedPage?->schedule_status ?? 'inactive';
            }
        );
    }

    public function scopeSearch($query, $search)
    {
        $query->where('ig_user_id', $search)->orWhere('username', 'like', "%{$search}%");
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ! empty($value) ? asset('images/' . $value) : no_image()
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn () => 'instagram'
        );
    }

    /**
     * Match Page model: store a future datetime from current time (page token handling).
     */
    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $value = time();
                $next_time = strtotime('+80 days', $value);

                return date('Y-m-d H:i:s', $next_time);
            },
            get: function ($value) {
                if (empty($value)) {
                    return 0;
                }

                return strtotime($value);
            }
        );
    }

    public function validToken()
    {
        $now = strtotime(date('Y-m-d H:i:s'));
        $expires_in = $this->expires_in;

        return $expires_in >= $now;
    }

    public function getAccountIdAttribute()
    {
        return $this->ig_user_id;
    }
}
