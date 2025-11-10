<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    private function formatNotification($notification)
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => [
                'icon' => $notification->data['icon'] ?? '🔔',
                'message' => $notification->data['message'] ?? 'Notification',
                'description' => $notification->data['description'] ?? null,
                'action' => $notification->data['action'] ?? null,
                'url' => $notification->data['url'] ?? null,
            ],
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at->format('M Y'),
            'time_ago' => $notification->created_at->diffForHumans(),
        ];
    }

    public function index(Request $request)
    {
        $notifications = Auth::user()->notifications()->latest()->take(20)->get()->map(function ($notification) {
            return $this->formatNotification($notification);
        });

        return Inertia::render('notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with([
            'notifications' => Auth::user()->notifications()->latest()->take(20)->get()->map(function ($notification) {
                return $this->formatNotification($notification);
            })
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back()->with([
            'notifications' => Auth::user()->notifications()->latest()->take(20)->get()->map(function ($notification) {
                return $this->formatNotification($notification);
            })
        ]);
    }

    public function markAsUnread($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) {
            $notification->update(['read_at' => null]);
        }

        return redirect()->back()->with([
            'notifications' => Auth::user()->notifications()->latest()->take(20)->get()->map(function ($notification) {
                return $this->formatNotification($notification);
            })
        ]);
    }
}
