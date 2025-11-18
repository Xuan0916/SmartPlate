<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // 🔔 List notifications
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Build dynamic link based on target_type
        foreach ($notifications as $note) {
            if ($note->target_type === 'inventory') {
                $note->link = route('inventory.index');

            } elseif ($note->target_type === 'donation') {
                // Donation has item-level detail
                $note->link = route('donation.index');

            } elseif ($note->target_type === 'meal') {
                // Meal plan page
                $note->link = route('mealplans.index');

            } else {
                // Default fallback (previous behaviour)
                $note->link = route('inventory.index');
            }
        }

        return view('managefoodinventory.notifications', compact('notifications'));
    }


    // Mark single as read
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['status' => 'read']);

        return back()->with('success', 'Notification marked as read.');
    }


    // Mark all as read
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('status', 'new')
            ->update(['status' => 'read']);

        return back()->with('success', 'All notifications marked as read.');
    }
}
