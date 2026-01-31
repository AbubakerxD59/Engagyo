<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "fb_id",
        "page_id",
        "name",
        "profile_image",
        "status",
        "last_fetch",
        "shuffle",
        "rss_paused",
        "access_token",
        "expires_in",
    ];

    protected $casts = [
        'rss_paused' => 'boolean',
    ];

    protected $appends = ["type"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function facebook()
    {
        return $this->belongsTo(Facebook::class, 'fb_id', 'id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'account_id', 'id');
    }

    public function domains()
    {
        return $this->hasMany(Domain::class, 'account_id', 'id');
    }
    public function timeslots()
    {
        return $this->hasMany(Timeslot::class, "account_id", "id")->where("account_type", "facebook");
    }

    public function scopeConnected($query, $search)
    {
        $query->where('fb_id', $search['fb_id'])->where('page_id', $search['page_id']);
    }

    public function scopeSearch($query, $search)
    {
        $query->where('fb_id', $search)->orWhere('page_id', $search)->orWhere('name', $search);
    }

    public function scopeActive($query)
    {
        $query->where('status', '1');
    }

    public function scopeShuffleEnabled($query){
        $query->where('shuffle', '1');
    }

    public function scopewhereScheduledActive($query)
    {
        $query->where("schedule_status", "active");
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "facebook";
            }
        );
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => !empty($value) ? asset("images/" . $value) : no_image()
        );
    }

    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                $value = time();
                $next_time = strtotime('+80 days', $value);
                $expires_in = date("Y-m-d H:i:s", $next_time);
                return $expires_in;
            },
            get: function ($value) {
                $expires_in = strtotime($value);
                return $expires_in;
            }
        );
    }

    public function validToken()
    {
        $now = strtotime(date("Y-m-d H:i:s"));
        $expires_in = $this->expires_in;
        return $expires_in >= $now ? true : false;
    }

    public function getAccountIdAttribute()
    {
        return $this->page_id;
    }

    public function getlastFetchedAttribute()
    {
        $date = $this->last_fetch;
        return $date ? date("jS M, Y h:i A", strtotime($date)) : '';
    }

    public function getAccountNameAttribute()
    {
        $account = $this->facebook()->first();
        return $account ? $account->username : "";
    }

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);
    }
}
