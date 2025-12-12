<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get notifications for the authenticated user (both read and unread)
     * Includes user-specific notifications and system notifications
     * Latest notifications shown first
     */
    public function fetch()
    {
        try {
            $user = Auth::user();
            
            // Get all notifications for this user (both read and unread)
            // Includes: user-specific notifications + system notifications (is_system = true, user_id = null)
            $notifications = Notification::where(function($query) use ($user) {
                // User-specific notifications
                $query->where('user_id', $user->id)
                      // System notifications (shown to all users)
                      ->orWhere(function($q) {
                          $q->where('is_system', true)
                            ->whereNull('user_id');
                      });
            })
            ->orderBy('created_at', 'desc') // Latest first
            ->limit(100) // Limit to 100 most recent
            ->get()
            ->map(function($notification) use ($user) {
                // Check if notification is read
                $isRead = false;
                
                if ($notification->is_system) {
                    // For system notifications, check pivot table
                    $pivot = $notification->users()->where('user_id', $user->id)->first();
                    $isRead = $pivot ? (bool) $pivot->pivot->is_read : false;
                } else {
                    // For user-specific notifications, use is_read column
                    $isRead = (bool) $notification->is_read;
                }
                
                return [
                    'id' => $notification->id,
                    'title' => $notification->title ?? 'Notification',
                    'body' => $notification->body,
                    'modal' => $notification->modal,
                    'is_system' => $notification->is_system,
                    'is_read' => $isRead,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            });

            // Count unread notifications
            $unreadCount = $notifications->where('is_read', false)->count();

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => $unreadCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where(function($query) use ($user, $id) {
                $query->where('id', $id)
                      ->where(function($q) use ($user) {
                          // User-specific notification
                          $q->where('user_id', $user->id)
                            // Or system notification
                            ->orWhere(function($q2) {
                                $q2->where('is_system', true)
                                   ->whereNull('user_id');
                            });
                      });
            })->first();

            if ($notification) {
                if ($notification->is_system) {
                    // For system notifications, use pivot table
                    $pivot = $notification->users()->where('user_id', $user->id)->first();
                    if ($pivot) {
                        $notification->users()->updateExistingPivot($user->id, [
                            'is_read' => true,
                            'read_at' => now()
                        ]);
                    } else {
                        $notification->users()->attach($user->id, [
                            'is_read' => true,
                            'read_at' => now()
                        ]);
                    }
                } else {
                    // For user-specific notifications, update is_read column
                    $notification->update(['is_read' => true]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            
            // Mark user-specific notifications as read
            Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
            
            // Mark system notifications as read (using pivot table)
            $systemNotifications = Notification::where('is_system', true)
                ->whereNull('user_id')
                ->get();
            
            foreach ($systemNotifications as $notification) {
                $pivot = $notification->users()->where('user_id', $user->id)->first();
                if ($pivot && !$pivot->pivot->is_read) {
                    $notification->users()->updateExistingPivot($user->id, [
                        'is_read' => true,
                        'read_at' => now()
                    ]);
                } else if (!$pivot) {
                    $notification->users()->attach($user->id, [
                        'is_read' => true,
                        'read_at' => now()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

