<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "title",
        "body",
        "modal",
        "is_read",
        "is_system",
    ];

    protected $casts = [
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
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere(function($q2) {
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
        return $query->where(function($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere(function($q2) {
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
}
