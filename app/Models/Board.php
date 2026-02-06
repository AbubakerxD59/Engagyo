<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "pin_id",
        "board_id",
        "name",
        "status",
        "last_fetch",
        "shuffle",
        "rss_paused",
        "schedule_status"
    ];

    protected $casts = [
        'rss_paused' => 'boolean',
    ];

    protected $appends = ["type"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pinterest()
    {
        return $this->belongsTo(Pinterest::class, 'pin_id', 'id');
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
        return $this->hasMany(Timeslot::class, "account_id", "id")->where("account_type", "pinterest");
    }

    public function scopeSearch($query, $search)
    {
        $query->where('pin_id', $search)->orWhere('board_id', $search)->orWhere('name', $search);
    }

    public function scopeActive($query)
    {
        $query->where('status', '1');
    }

    public function scopeShuffleEnabled($query)
    {
        $query->where('shuffle', '1');
    }

    public function scopewhereScheduledActive($query)
    {
        $query->where("schedule_status", "active");
    }

    public function scopeConnected($query, $search)
    {
        $query->where('pin_id', $search['pin_id'])->where('board_id', $search['board_id']);
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "pinterest";
            }
        );
    }

    public function getPinterest($pin_id)
    {
        $pinterest = $this->pinterest()->where("pin_id", $pin_id)->first();
        return $pinterest;
    }

    public function getAccountIdAttribute()
    {
        return $this->board_id;
    }

    public function getlastFetchedAttribute()
    {
        $date = $this->last_fetch;
        $date = strtotime($date);
        return $date ? date("jS M, Y h:i A", $date) : '';
    }

    public function getAccountNameAttribute()
    {
        $account = $this->pinterest()->first();
        return $account ? $account->username : "";
    }

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);
    }
}
