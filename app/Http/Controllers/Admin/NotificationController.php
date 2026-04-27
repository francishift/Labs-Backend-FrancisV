<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * View for all notifications history.
     */
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return Inertia::render('Admin/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * API endpoint to silently fetch unread notifications via Axios.
     */
    public function fetchUnread(Request $request)
    {
        $unread = $request->user()->unreadNotifications()->take(5)->get();
        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $unread
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }

        return redirect()->back();
    }

    /**
     * Delete all notifications for the user.
     */
    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return redirect()->back();
    }
}
