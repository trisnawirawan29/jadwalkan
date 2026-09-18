<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->latest()->paginate(12)]);
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless($notification->notifiable_id === $request->user()->id && $notification->notifiable_type === get_class($request->user()), 403);
        $notification->markAsRead();
        AuditLogger::record('notification.read', 'Notifikasi ditandai sudah dibaca.', $notification, ['read_at' => null], ['read_at' => now()->toDateTimeString()]);

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        AuditLogger::record('notification.read_all', 'Semua notifikasi ditandai sudah dibaca.', $request->user());

        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}
