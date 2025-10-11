<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    /**
     * Display all notifications (latest first)
     */
    public function index()
    {
        $notifications = Notification::latest()->paginate(20);
        return view('managefoodinventory.notifications', compact('notifications'));
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);

        // ✅ 支持两种字段情况：status 或 is_read
        if (Schema::hasColumn('notifications', 'status')) {
            $notification->update(['status' => 'read']);
        } elseif (Schema::hasColumn('notifications', 'is_read')) {
            $notification->update(['is_read' => true]);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        // ✅ 兼容两种列结构
        if (Schema::hasColumn('notifications', 'status')) {
            Notification::where('status', 'new')->update(['status' => 'read']);
        } elseif (Schema::hasColumn('notifications', 'is_read')) {
            Notification::where('is_read', false)->update(['is_read' => true]);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'All notifications marked as read.');
    }
}
