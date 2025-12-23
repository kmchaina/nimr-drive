<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser();
        
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $this->getCurrentUser();
        
        if ($notification->user_id !== $user->id) {
            abort(403);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(Request $request)
    {
        $user = $this->getCurrentUser();
        
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request)
    {
        $user = $this->getCurrentUser();
        
        Notification::where('user_id', $user->id)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Create a notification (Internal helper)
     */
    public static function create(int $userId, string $title, string $message, string $type = 'info', ?string $link = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
    }

    private function getCurrentUser(): User
    {
        $sessionUser = session('user');
        if (!$sessionUser) abort(401);
        return User::find($sessionUser['id']);
    }
}
