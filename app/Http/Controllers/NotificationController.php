<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    // ✅ Display only private notifications of logged-in user
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('managefoodinventory.notifications', compact('notifications'));
    }

    // ✅ Mark single as read
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);

        if (Schema::hasColumn('notifications', 'status')) {
            $notification->update(['status' => 'read']);
        } elseif (Schema::hasColumn('notifications', 'is_read')) {
            $notification->update(['is_read' => true]);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    // ✅ Mark all as read
    public function markAllAsRead()
    {
        if (Schema::hasColumn('notifications', 'status')) {
            Notification::where('user_id', Auth::id())
                ->where('status', 'new')
                ->update(['status' => 'read']);
        } elseif (Schema::hasColumn('notifications', 'is_read')) {
            Notification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return redirect()->route('notifications.index')
            ->with('success', 'All notifications marked as read.');
    }
}
