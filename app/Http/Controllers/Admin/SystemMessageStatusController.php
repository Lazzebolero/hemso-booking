<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemMessage;
use Illuminate\Support\Facades\DB;

class SystemMessageStatusController extends Controller
{
    public function read(SystemMessage $systemMessage)
    {
        $user = auth()->user();

        DB::table('system_message_user')->updateOrInsert(
            [
                'system_message_id' => $systemMessage->id,
                'user_id' => $user->id,
            ],
            [
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back();
    }

    public function dismiss(SystemMessage $systemMessage)
    {
        $user = auth()->user();

        DB::table('system_message_user')->updateOrInsert(
            [
                'system_message_id' => $systemMessage->id,
                'user_id' => $user->id,
            ],
            [
                'dismissed_at' => now(),
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back();
    }

    public function restore(SystemMessage $systemMessage)
    {
        $user = auth()->user();

        DB::table('system_message_user')->updateOrInsert(
            [
                'system_message_id' => $systemMessage->id,
                'user_id' => $user->id,
            ],
            [
                'dismissed_at' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Meddelandet visas igen.');
    }

    public function acknowledge(SystemMessage $systemMessage)
    {
        $user = auth()->user();

        DB::table('system_message_user')->updateOrInsert(
            [
                'system_message_id' => $systemMessage->id,
                'user_id' => $user->id,
            ],
            [
                'acknowledged_at' => now(),
                'read_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Meddelandet har kvitterats.');
    }
}