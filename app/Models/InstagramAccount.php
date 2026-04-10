<?php

namespace App\Models;

use App\Models\Relations\BelongsToInstagramLinkedPage;
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
        'schedule_status',
        'url_shortener_enabled',
        'last_fetch',
        'shuffle',
        'rss_paused',
    ];

    protected $casts = [
        'url_shortener_enabled' => 'boolean',
        'rss_paused' => 'boolean',
        'last_fetch' => 'datetime',
    ];

    protected $appends = ['type'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function facebook()
    {
        return $this->belongsTo(Facebook::class, 'facebook_id', 'id');
    }

    /**
     * Optional Facebook Page row (same Graph page_id). Used only to inherit timeslots until Instagram has its own.
     */
    public function linkedPage()
    {
        $instance = $this->newRelatedInstance(Page::class);

        return new BelongsToInstagramLinkedPage(
            $instance->newQuery(),
            $this,
            'page_id',
            'page_id',
            __FUNCTION__
        );
    }

    public function getTimeslotsAttribute()
    {
        $this->loadMissing('linkedPage.timeslots');

        return $this->linkedPage?->timeslots ?? collect();
    }

    public function scopeWhereScheduledActive($query)
    {
        $query->where('schedule_status', 'active');
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
