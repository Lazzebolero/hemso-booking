<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SecurityOverviewController extends Controller
{
    public function index(): View
    {
        $since24h = now()->subDay();
        $since7d = now()->subDays(7);

        $stats = [
            'failed_24h' => LoginEvent::where('event_type', 'failed')
                ->where('occurred_at', '>=', $since24h)
                ->count(),

            'logins_24h' => LoginEvent::where('event_type', 'login')
                ->where('occurred_at', '>=', $since24h)
                ->count(),

            'failed_7d' => LoginEvent::where('event_type', 'failed')
                ->where('occurred_at', '>=', $since7d)
                ->count(),

            'unique_failed_ips_24h' => LoginEvent::where('event_type', 'failed')
                ->where('occurred_at', '>=', $since24h)
                ->whereNotNull('ip_address')
                ->distinct('ip_address')
                ->count('ip_address'),
        ];

        $topFailedIps = LoginEvent::query()
            ->select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since7d)
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        $topFailedEmails = LoginEvent::query()
            ->select('email', DB::raw('COUNT(*) as attempts'))
            ->where('event_type', 'failed')
            ->where('occurred_at', '>=', $since7d)
            ->whereNotNull('email')
            ->groupBy('email')
            ->orderByDesc('attempts')
            ->limit(10)
            ->get();

        $recentFailed = LoginEvent::query()
            ->where('event_type', 'failed')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        $recentLogins = LoginEvent::with('user')
            ->where('event_type', 'login')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        $riskLevel = 'ok';

        if ($stats['failed_24h'] >= 20 || $stats['unique_failed_ips_24h'] >= 5) {
            $riskLevel = 'high';
        } elseif ($stats['failed_24h'] >= 5) {
            $riskLevel = 'medium';
        }

        return view('admin.security-overview.index', compact(
            'stats',
            'topFailedIps',
            'topFailedEmails',
            'recentFailed',
            'recentLogins',
            'riskLevel'
        ));
    }
}