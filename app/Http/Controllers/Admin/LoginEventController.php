<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginEventController extends Controller
{
    public function index(Request $request): View
    {
        $events = LoginEvent::with('user')
            ->when($request->filled('event_type'), function ($query) use ($request) {
                $query->where('event_type', $request->event_type);
            })
            ->when($request->filled('email'), function ($query) use ($request) {
                $query->where('email', 'like', '%' . $request->email . '%');
            })
            ->when($request->filled('ip_address'), function ($query) use ($request) {
                $query->where('ip_address', 'like', '%' . $request->ip_address . '%');
            })
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'logins_today' => LoginEvent::where('event_type', 'login')
                ->whereDate('occurred_at', today())
                ->count(),

            'failed_today' => LoginEvent::where('event_type', 'failed')
                ->whereDate('occurred_at', today())
                ->count(),

            'unique_ips_today' => LoginEvent::whereDate('occurred_at', today())
                ->whereNotNull('ip_address')
                ->distinct('ip_address')
                ->count('ip_address'),
        ];

        return view('admin.login-events.index', compact('events', 'stats'));
    }
}