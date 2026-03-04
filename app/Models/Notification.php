<?php

namespace App\Models;

use App\Services\TimezoneService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table =  "notifications";

    protected static function booted(): void
    {
        static::creating(function (Notification $notification) {
            $user = $notification->user_id ? User::find($notification->user_id) : null;
            $tz = TimezoneService::getUserTimezone($user);
            $value = $notification->created_at
                ? Carbon::parse($notification->created_at)->setTimezone($tz)->format('Y-m-d H:i:s')
                : Carbon::now($tz)->format('Y-m-d H:i:s');
            // Store in UTC with seconds (Notification only; TimezoneService::toUtc uses Y-m-d H:i for others)
            $notification->created_at = Carbon::parse($value, $tz)->utc();
        });
    }

    protected $fillable = [
        "user_id",
        "title",
        "body",
        "modal",
        "is_read",
        "is_system",
    ];

    protected $casts = [
        'created_at' => 'datetime',
        "body" => "array",
        "is_read" => "boolean",
        "is_system" => "boolean",
    ];

    /**
     * Get the user that owns the notification
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users that have read this system notification
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->withPivot('is_read', 'read_at')
            ->withTimestamps();
    }

    /**
     * Scope to order by latest first (newest on top)
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get system notifications
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get user-specific notifications
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere(function ($q2) {
                    $q2->where('is_system', true)
                        ->whereNull('user_id');
                });
        });
    }

    /**
     * Scope to get notifications visible to a user
     * Includes user-specific and system notifications
     */
    public function scopeVisibleTo($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->orWhere(function ($q2) {
                    $q2->where('is_system', true)
                        ->whereNull('user_id');
                });
        });
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Get created_at formatted in a given user's timezone (e.g. for display in navbar).
     * Stored value is UTC; pass the viewer's user to show in their timezone.
     */
    public function getCreatedAtForUser(?User $user): string
    {
        $utc = $this->getRawOriginal('created_at');
        if (empty($utc)) {
            return '';
        }
        return TimezoneService::parseUtcToUserCarbon($utc, $user)->diffForHumans();
    }
}
