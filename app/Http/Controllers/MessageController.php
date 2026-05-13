<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $conversations = Conversation::query()
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with([
                'users',
                'latestMessage.sender',
                'participants' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('messages.index', compact('conversations'));
    }

    public function create()
    {
        $users = User::query()
            ->where('id', '!=', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('messages.create', compact('users'));
    }

    public function storeDirect(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'body' => ['required', 'string'],
        ]);

        $authUserId = auth()->id();
        $otherUserId = (int) $data['user_id'];

        if ($authUserId === $otherUserId) {
            return back()->withErrors([
                'user_id' => 'Du kan inte skicka PM till dig själv.',
            ]);
        }

        $conversation = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', function ($query) use ($authUserId) {
                $query->where('user_id', $authUserId);
            })
            ->whereHas('participants', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->withCount('participants')
            ->get()
            ->first(function ($conversation) {
                return $conversation->participants_count === 2;
            });

        DB::transaction(function () use (&$conversation, $authUserId, $otherUserId, $data) {
            if (!$conversation) {
                $conversation = Conversation::create([
                    'type' => 'direct',
                    'created_by' => $authUserId,
                    'is_active' => true,
                    'last_message_at' => now(),
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $authUserId,
                    'last_read_at' => now(),
                ]);

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $otherUserId,
                    'last_read_at' => null,
                ]);
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $authUserId,
                'body' => trim($data['body']),
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $authUserId)
                ->update(['last_read_at' => now()]);
        });

        return redirect()
            ->route('messages.show', $conversation)
            ->with('success', 'Meddelandet skickades.');
    }

    public function show(Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $conversation->load([
            'users',
            'messages.sender',
            'participants',
        ]);

        $this->markConversationAsRead($conversation, auth()->id());

        return view('messages.show', compact('conversation'));
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($conversation, $data) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => auth()->id(),
                'body' => trim($data['body']),
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', auth()->id())
                ->update(['last_read_at' => now()]);
        });

        return redirect()->route('messages.show', $conversation);
    }

    public function markRead(Conversation $conversation)
    {
        $this->authorizeParticipant($conversation);

        $this->markConversationAsRead($conversation, auth()->id());

        return back();
    }

    protected function authorizeParticipant(Conversation $conversation): void
    {
        $isParticipant = $conversation->participants()
            ->where('user_id', auth()->id())
            ->exists();

        abort_unless($isParticipant, 403);
    }

    protected function markConversationAsRead(Conversation $conversation, int $userId): void
    {
        ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => now()]);
    }
}