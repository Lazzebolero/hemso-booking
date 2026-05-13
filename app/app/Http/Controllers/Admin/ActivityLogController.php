<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('entity_type')) $query->where('entity_type', $request->entity_type);
        if ($request->filled('action')) $query->where('action', $request->action);
        if ($request->filled('entity_id')) $query->where('entity_id', $request->entity_id);

        return view('admin.activity-logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'users' => User::orderBy('name')->get(['id','name']),
            'entityTypes' => ActivityLog::query()->select('entity_type')->distinct()->pluck('entity_type'),
            'actions' => ActivityLog::query()->select('action')->distinct()->pluck('action'),
        ]);
    }

    public function showEntityHistory(string $entityType, int $entityId)
    {
        $logs = ActivityLog::with('user')->where('entity_type', $entityType)->where('entity_id', $entityId)->latest()->paginate(25);
        return view('admin.activity-logs.entity-history', compact('logs', 'entityType', 'entityId'));
    }
}
