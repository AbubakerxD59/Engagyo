<?php

namespace App\Models;

use App\Models\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tiktok extends Model
{
    use HasFactory;

    protected $table = 'tiktoks';

    protected $fillable = [
        "user_id",
        "tiktok_id",
        "username",
        "display_name",
        "profile_image",
        "access_token",
        "expires_in",
        "refresh_token",
        "refresh_token_expires_in",
        "schedule_status",
        "last_fetch",
    ];

    protected $appends = ["type"];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function timeslots()
    {
        return $this->hasMany(Timeslot::class, "account_id", "id")->where("account_type", "tiktok");
    }

    public function scopeWhereScheduledActive($query)
    {
        $query->where("schedule_status", "active");
    }

    public function scopeSearch($query, $search)
    {
        $query->where('tiktok_id', $search)->orWhere("username", "like", "%{$search}%");
    }

    protected function expiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_numeric($value)) {
                    $now = strtotime(date("Y-m-d H:i:s"));
                    $expires_in = $now + $value;
                    return date("Y-m-d H:i:s", $expires_in);
                }
                return $value;
            },
            get: function ($value) {
                if (empty($value)) {
                    return 0;
                }
                $expires_in = strtotime($value);
                return $expires_in;
            }
        );
    }

    protected function refreshTokenExpiresIn(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_numeric($value)) {
                    $now = strtotime(date("Y-m-d H:i:s"));
                    $refresh_token_expires_in = $now + $value;
                    return date("Y-m-d H:i:s", $refresh_token_expires_in);
                }
                return $value;
            },
            get: function ($value) {
                if (empty($value)) {
                    return 0;
                }
                $refresh_token_expires_in = strtotime($value);
                return $refresh_token_expires_in;
            }
        );
    }

    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn($value) => !empty($value) ? asset("images/" . $value) : no_image()
        );
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "tiktok";
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
        return $this->tiktok_id;
    }

    public function getNameAttribute()
    {
        return $this->display_name ?? $this->username;
    }

    public function getAccountNameAttribute()
    {
        return $this->name;
    }

    public function getlastFetchedAttribute()
    {
        $date = $this->last_fetch;
        $date = strtotime($date);
        return $date ? date("jS M, Y h:i A", $date) : '';
    }

    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);
    }
}
