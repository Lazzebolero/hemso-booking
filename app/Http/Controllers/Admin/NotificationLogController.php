<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;

class NotificationLogController extends Controller
{
    public function index()
    {
        $logs = NotificationLog::latest()->paginate(50);

        return view('admin.notifications.logs.index', compact('logs'));
    }

    public function resend(NotificationLog $notificationLog, NotificationService $notificationService)
    {
        $notificationService->sendTemplate(
            key: $notificationLog->template_key,
            recipientEmail: $notificationLog->recipient_email,
            data: $notificationLog->payload ?? [],
        );

        return back()->with('success', 'Meddelandet skickades igen.');
    }
}