<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupChatController extends Controller
{
    public function create()
    {
        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('group-chats.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
            'body' => ['nullable', 'string'],
        ]);

        $participantIds = collect($data['participant_ids'])
            ->map(fn ($id) => (int) $id)
            ->push(auth()->id())
            ->unique()
            ->values();

        $conversation = DB::transaction(function () use ($data, $participantIds) {
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => trim($data['name']),
                'created_by' => auth()->id(),
                'is_active' => true,
                'last_message_at' => now(),
            ]);

            foreach ($participantIds as $userId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'last_read_at' => $userId === auth()->id() ? now() : null,
                ]);
            }

            if (!empty($data['body'])) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => auth()->id(),
                    'body' => trim($data['body']),
                ]);
            }

            return $conversation;
        });

        return redirect()
            ->route('messages.show', $conversation)
            ->with('success', 'Gruppchatt skapad.');
    }
}