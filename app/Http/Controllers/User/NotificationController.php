<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get unread notifications for the authenticated user
     * Includes user-specific notifications and system notifications
     */
    public function fetch()
    {
        try {
            $user = Auth::user();
            
            // Get unread notifications for this user
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
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(50) // Limit to 50 most recent
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title ?? 'Notification',
                    'body' => $notification->body,
                    'modal' => $notification->modal,
                    'is_system' => $notification->is_system,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'created_at_full' => $notification->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => $notifications->count()
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
                $notification->markAsRead();
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
            
            Notification::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere(function($q) {
                          $q->where('is_system', true)
                            ->whereNull('user_id');
                      });
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);

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

