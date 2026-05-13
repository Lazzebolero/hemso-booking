<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SystemMessageAlertMail;
use App\Models\SystemMessage;
use App\Models\User;
use App\Support\ActiveRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemMessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = SystemMessage::query()
            ->with('creator')
            ->when($request->boolean('only_unacknowledged'), function ($query) {
                $query->where('requires_ack', true);
            })
            ->orderByDesc('is_active')
            ->orderByDesc('priority')
            ->orderByDesc('starts_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $dismissedMessages = collect();

        if (auth()->check()) {
            $user = auth()->user();
            $activeRole = $this->currentRoleSlug();

            $dismissedMessages = SystemMessage::query()
                ->visibleNow()
                ->forRole($activeRole)
                ->whereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id)
                        ->whereNotNull('system_message_user.dismissed_at');
                })
                ->with(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])
                ->orderByDesc('starts_at')
                ->get();
        }

        return view('admin.system-messages.index', compact('messages', 'dismissedMessages'));
    }

    public function create(): View
    {
        $message = new SystemMessage();

        return view('admin.system-messages.form', compact('message'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = auth()->id();
        $data['next_reminder_at'] = $this->resolveNextReminderAt($data);

        $message = SystemMessage::create($data);

        $emailsSent = 0;

        if ($message->send_email && (int) $message->priority === 3) {
            $emailsSent = $this->sendAlertEmails($message);
        }

        $success = 'Systemmeddelandet skapades.';

        if ($emailsSent > 0) {
            $success .= ' E-post skickades till ' . $emailsSent . ' mottagare.';
        }

        return redirect()
            ->route('admin.system-messages.index')
            ->with('success', $success);
    }

    public function edit(SystemMessage $systemMessage): View
    {
        $message = $systemMessage;

        return view('admin.system-messages.form', compact('message'));
    }

    public function update(Request $request, SystemMessage $systemMessage): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['next_reminder_at'] = $this->resolveNextReminderAt($data, $systemMessage);

        $systemMessage->update($data);

        $emailsSent = 0;

        if ($systemMessage->send_email && (int) $systemMessage->priority === 3) {
            $emailsSent = $this->sendAlertEmails($systemMessage);
        }

        $success = 'Systemmeddelandet uppdaterades.';

        if ($emailsSent > 0) {
            $success .= ' E-post skickades till ' . $emailsSent . ' mottagare.';
        }

        return redirect()
            ->route('admin.system-messages.index')
            ->with('success', $success);
    }

    public function destroy(SystemMessage $systemMessage): RedirectResponse
    {
        $systemMessage->delete();

        return redirect()
            ->route('admin.system-messages.index')
            ->with('success', 'Systemmeddelandet togs bort.');
    }

    public function livePanel(): JsonResponse
    {
        $messages = collect();
        $unreadCount = 0;
        $importantUnread = collect();

        if (auth()->check()) {
            $user = auth()->user();
            $activeRole = $this->currentRoleSlug();

            $messages = SystemMessage::query()
                ->visibleNow()
                ->forRole($activeRole)
                ->notDismissedForUser($user->id)
                ->with(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])
                ->orderByDesc('priority')
                ->orderByDesc('starts_at')
                ->orderByDesc('created_at')
                ->get();

            $unreadMessages = $messages->filter(function ($message) {
                $pivotUser = $message->users->first();

                return ! $pivotUser || empty($pivotUser->pivot?->read_at);
            });

            $unreadCount = $unreadMessages->count();

            $importantUnread = $unreadMessages
                ->filter(function ($message) {
                    return (bool) $message->is_important || (int) $message->priority === 3;
                })
                ->values();
        }

        return response()->json([
            'unread_count' => $unreadCount,
            'important_unread' => $importantUnread->map(function ($message) {
                return [
                    'id' => $message->id,
                    'title' => $message->title,
                    'body' => $message->body,
                    'is_important' => (bool) $message->is_important,
                    'priority' => (int) $message->priority,
                    'popup_only' => (bool) $message->popup_only,
                    'requires_ack' => (bool) $message->requires_ack,
                ];
            })->values(),
        ]);
    }

    public function forcePopupPanel(): JsonResponse
    {
        $forcedMessages = collect();

        if (auth()->check()) {
            $user = auth()->user();
            $activeRole = $this->currentRoleSlug();

            $forcedMessages = SystemMessage::query()
                ->visibleNow()
                ->forRole($activeRole)
                ->notDismissedForUser($user->id)
                ->with(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                }])
                ->where('popup_only', true)
                ->orderByDesc('priority')
                ->orderByDesc('starts_at')
                ->get()
                ->filter(function ($message) {
                    $pivotUser = $message->users->first();

                    $isUnread = ! $pivotUser || empty($pivotUser->pivot?->read_at);

                    $needsAck = $message->requires_ack
                        && (! $pivotUser || empty($pivotUser->pivot?->acknowledged_at));

                    return $isUnread || $needsAck;
                })
                ->values();
        }

        return response()->json([
            'messages' => $forcedMessages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'title' => $message->title,
                    'body' => $message->body,
                    'priority' => (int) $message->priority,
                    'requires_ack' => (bool) $message->requires_ack,
                ];
            })->values(),
        ]);
    }

    public function readers(Request $request, SystemMessage $systemMessage): View
    {
        [$rows, $stats] = $this->buildReaderRows(
            $systemMessage,
            $request->boolean('only_unacknowledged')
        );

        return view('admin.system-messages.readers', [
            'message' => $systemMessage,
            'rows' => $rows,
            'stats' => $stats,
            'onlyUnacknowledged' => $request->boolean('only_unacknowledged'),
        ]);
    }

    public function exportReaders(Request $request, SystemMessage $systemMessage): StreamedResponse
    {
        [$rows] = $this->buildReaderRows(
            $systemMessage,
            $request->boolean('only_unacknowledged')
        );

        $filename = 'system-message-readers-' . $systemMessage->id . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Namn',
                'E-post',
                'Roller',
                'Läst',
                'Kvitterad',
                'Stängd',
            ], ';');

            foreach ($rows as $row) {
                $roles = $row['user']->roles
                    ? $row['user']->roles->pluck('name')->implode(', ')
                    : '';

                fputcsv($handle, [
                    $row['user']->name,
                    $row['user']->email,
                    $roles,
                    $row['read_at'] ? \Carbon\Carbon::parse($row['read_at'])->format('Y-m-d H:i') : '',
                    $row['acknowledged_at'] ? \Carbon\Carbon::parse($row['acknowledged_at'])->format('Y-m-d H:i') : '',
                    $row['dismissed_at'] ? \Carbon\Carbon::parse($row['dismissed_at'])->format('Y-m-d H:i') : '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function reminderSweep(Request $request)
    {
        $processed = 0;
        $emailsSent = 0;

        $messages = SystemMessage::query()
            ->where('is_active', true)
            ->where('requires_ack', true)
            ->whereNotNull('remind_every_minutes')
            ->where(function ($query) {
                $query->whereNull('next_reminder_at')
                    ->orWhere('next_reminder_at', '<=', now());
            })
            ->get();

        foreach ($messages as $message) {
            $processed++;
            $emailsSent += $this->sendReminderEmails($message);

            $message->update([
                'last_reminder_at' => now(),
                'next_reminder_at' => now()->addMinutes((int) $message->remind_every_minutes),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'processed' => $processed,
                'emails_sent' => $emailsSent,
            ]);
        }

        return redirect()
            ->route(ActiveRole::routePrefix() . '.system-messages.index')
            ->with(
                'success',
                "Påminnelsekörning klar. Bearbetade: {$processed}. E-post skickade: {$emailsSent}."
            );
    }

    private function buildReaderRows(SystemMessage $systemMessage, bool $onlyUnacknowledged = false): array
    {
        $roles = $this->normalizeRoles($systemMessage->target_roles ?? ['all']);

        $users = User::query()
            ->with('roles')
            ->when(! in_array('all', $roles, true), function ($query) use ($roles) {
                $query->whereHas('roles', function ($roleQuery) use ($roles) {
                    $roleQuery->whereIn('slug', $roles);
                });
            })
            ->orderBy('name')
            ->get();

        $systemMessage->load(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email');
        }]);

        $readMap = $systemMessage->users->keyBy('id');

        $rows = $users->map(function ($user) use ($readMap) {
            $readUser = $readMap->get($user->id);

            return [
                'user' => $user,
                'read_at' => $readUser?->pivot?->read_at,
                'acknowledged_at' => $readUser?->pivot?->acknowledged_at,
                'dismissed_at' => $readUser?->pivot?->dismissed_at,
            ];
        });

        if ($onlyUnacknowledged) {
            $rows = $rows->filter(function ($row) {
                return empty($row['acknowledged_at']);
            })->values();
        }

        $stats = [
            'total' => $rows->count(),
            'read' => $rows->filter(fn ($row) => ! empty($row['read_at']))->count(),
            'acknowledged' => $rows->filter(fn ($row) => ! empty($row['acknowledged_at']))->count(),
            'unacknowledged' => $rows->filter(fn ($row) => empty($row['acknowledged_at']))->count(),
        ];

        return [$rows, $stats];
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['in:all,admin,host,guide,restaurant'],
            'is_important' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'in:1,2,3'],
            'popup_only' => ['nullable', 'boolean'],
            'requires_ack' => ['nullable', 'boolean'],
            'send_email' => ['nullable', 'boolean'],
            'remind_every_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $roles = $this->normalizeRoles($data['target_roles'] ?? ['all']);

        if (in_array('all', $roles, true)) {
            $roles = ['all'];
        }

        $data['target_roles'] = $roles;
        $data['is_important'] = $request->boolean('is_important');
        $data['popup_only'] = $request->boolean('popup_only');
        $data['requires_ack'] = $request->boolean('requires_ack');
        $data['send_email'] = $request->boolean('send_email');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['message_type'] = 'message';

        if (empty($data['requires_ack'])) {
            $data['remind_every_minutes'] = null;
            $data['last_reminder_at'] = null;
            $data['next_reminder_at'] = null;
        }

        return $data;
    }

    private function resolveNextReminderAt(array $data, ?SystemMessage $existing = null)
    {
        if (empty($data['requires_ack']) || empty($data['remind_every_minutes'])) {
            return null;
        }

        if (
            $existing
            && $existing->next_reminder_at
            && (int) $existing->remind_every_minutes === (int) $data['remind_every_minutes']
        ) {
            return $existing->next_reminder_at;
        }

        return now()->addMinutes((int) $data['remind_every_minutes']);
    }

    private function resolveTargetUsers(SystemMessage $message)
    {
        $roles = $this->normalizeRoles($message->target_roles ?? ['all']);

        return User::query()
            ->with('roles')
            ->when(! in_array('all', $roles, true), function ($query) use ($roles) {
                $query->whereHas('roles', function ($roleQuery) use ($roles) {
                    $roleQuery->whereIn('slug', $roles);
                });
            })
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }

    private function sendAlertEmails(SystemMessage $message): int
    {
        $count = 0;

        foreach ($this->resolveTargetUsers($message) as $user) {
            Mail::to($user->email)->send(new SystemMessageAlertMail($message));
            $count++;
        }

        return $count;
    }

    private function sendReminderEmails(SystemMessage $message): int
    {
        $count = 0;

        $message->load(['users']);

        $readMap = $message->users->keyBy('id');

        foreach ($this->resolveTargetUsers($message) as $user) {
            $pivotUser = $readMap->get($user->id);

            if ($pivotUser && ! empty($pivotUser->pivot?->acknowledged_at)) {
                continue;
            }

            Mail::to($user->email)->send(new SystemMessageAlertMail($message));
            $count++;
        }

        return $count;
    }

    private function normalizeRoles($roles): array
    {
        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            $roles = is_array($decoded) ? $decoded : [$roles];
        }

        if (! is_array($roles)) {
            $roles = ['all'];
        }

        $roles = array_values(array_filter($roles));

        return $roles ?: ['all'];
    }

    private function currentRoleSlug(): ?string
    {
        if (session()->has('active_role')) {
            return session('active_role');
        }

        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $user->loadMissing('roles');

        return $user->roles->first()?->slug;
    }
}