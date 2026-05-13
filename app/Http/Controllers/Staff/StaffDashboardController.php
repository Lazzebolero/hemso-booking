<?php

namespace App\Http\Controllers\Staff;

use App\Models\ConversationParticipant;
use App\Models\StaffDocument;
use App\Models\SystemMessage;
use App\Models\WorkShift;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StaffDashboardController extends StaffBaseController
{
    public function index(): View
    {
        $this->authorizeStaffAccess();

        $user = auth()->user();
        $today = now()->toDateString();

        $todayShift = WorkShift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', $today)
            ->orderBy('start_time')
            ->first();

        $upcomingShifts = WorkShift::query()
            ->where('user_id', $user->id)
            ->whereDate('shift_date', '>=', $today)
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        $activeRole = method_exists($user, 'activeRole')
            ? $user->activeRole()
            : null;

        $activeRoleSlug = is_object($activeRole)
            ? ($activeRole->slug ?? null)
            : null;

        $systemMessagesQuery = SystemMessage::query()
            ->visibleNow()
            ->when($activeRoleSlug, fn ($query) => $query->forRole($activeRoleSlug))
            ->notDismissedForUser($user->id)
            ->with(['users' => function ($query) use ($user) {
                $query->where('users.id', $user->id);
            }]);

        if (Schema::hasColumn('system_messages', 'starts_at')) {
            $systemMessagesQuery->orderByDesc('starts_at');
        }

        if (Schema::hasColumn('system_messages', 'created_at')) {
            $systemMessagesQuery->orderByDesc('created_at');
        }

        $systemMessages = $systemMessagesQuery
            ->limit(5)
            ->get();

        $unreadSystemMessagesCount = SystemMessage::query()
            ->visibleNow()
            ->when($activeRoleSlug, fn ($query) => $query->forRole($activeRoleSlug))
            ->notDismissedForUser($user->id)
            ->whereDoesntHave('users', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                    ->whereNotNull('system_message_user.read_at');
            })
            ->count();

        $conversationParticipantsQuery = ConversationParticipant::query()
            ->with('conversation')
            ->where('user_id', $user->id);

        if (Schema::hasColumn('conversation_participants', 'updated_at')) {
            $conversationParticipantsQuery->orderByDesc('updated_at');
        } elseif (Schema::hasColumn('conversation_participants', 'created_at')) {
            $conversationParticipantsQuery->orderByDesc('created_at');
        }

        $conversationParticipants = $conversationParticipantsQuery
            ->limit(5)
            ->get();

        $latestDocuments = StaffDocument::query()
            ->active()
            ->visibleTo($user)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('staff.dashboard', [
            'user' => $user,
            'todayShift' => $todayShift,
            'upcomingShifts' => $upcomingShifts,
            'systemMessages' => $systemMessages,
            'unreadSystemMessagesCount' => $unreadSystemMessagesCount,
            'conversationParticipants' => $conversationParticipants,
            'unreadMessagesCount' => null,
            'latestDocuments' => $latestDocuments,
        ]);
    }
}