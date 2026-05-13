<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AppPulseController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'urgent_messages' => $this->urgentMessagesCount($user),
            'unread_pm' => $this->unreadPmCount($user),
            'tours_version' => $this->toursVersion($user),
            'upcoming_tour' => $this->upcomingTour($user),
        ]);
    }

    private function urgentMessagesCount($user): int
    {
        if (! $user) {
            return 0;
        }

        foreach (['system_messages', 'systemmeddelanden', 'messages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'is_urgent')) {
                $query->where('is_urgent', true);
            } elseif (Schema::hasColumn($table, 'urgent')) {
                $query->where('urgent', true);
            } elseif (Schema::hasColumn($table, 'priority')) {
                $query->where('priority', 'urgent');
            } elseif (Schema::hasColumn($table, 'is_important')) {
                $query->where('is_important', true);
            } else {
                continue;
            }

            if (Schema::hasColumn($table, 'is_active')) {
                $query->where('is_active', true);
            } elseif (Schema::hasColumn($table, 'active')) {
                $query->where('active', true);
            }

            if (Schema::hasColumn($table, 'starts_at')) {
                $query->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                });
            } elseif (Schema::hasColumn($table, 'published_at')) {
                $query->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                });
            }

            if (Schema::hasColumn($table, 'ends_at')) {
                $query->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                });
            } elseif (Schema::hasColumn($table, 'expires_at')) {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                });
            }

            return (int) $query->count();
        }

        return 0;
    }

    private function unreadPmCount($user): int
    {
        if (! $user) {
            return 0;
        }

        if (Schema::hasTable('conversations') && Schema::hasTable('conversation_participants')) {
            return (int) DB::table('conversations')
                ->join('conversation_participants', 'conversation_participants.conversation_id', '=', 'conversations.id')
                ->where('conversation_participants.user_id', $user->id)
                ->whereNotNull('conversations.last_message_at')
                ->where(function ($query) {
                    $query->whereNull('conversation_participants.last_read_at')
                        ->orWhereColumn('conversation_participants.last_read_at', '<', 'conversations.last_message_at');
                })
                ->count();
        }

        foreach (['private_messages', 'pm_messages', 'messages'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'recipient_id')) {
                $query->where('recipient_id', $user->id);
            } elseif (Schema::hasColumn($table, 'to_user_id')) {
                $query->where('to_user_id', $user->id);
            } elseif (Schema::hasColumn($table, 'receiver_id')) {
                $query->where('receiver_id', $user->id);
            } else {
                continue;
            }

            if (Schema::hasColumn($table, 'read_at')) {
                $query->whereNull('read_at');
            } elseif (Schema::hasColumn($table, 'is_read')) {
                $query->where('is_read', false);
            } elseif (Schema::hasColumn($table, 'read')) {
                $query->where('read', false);
            } else {
                continue;
            }

            return (int) $query->count();
        }

        return 0;
    }

    private function toursVersion($user): ?string
    {
        if (! $user || ! Schema::hasTable('tours') || ! Schema::hasColumn('tours', 'updated_at')) {
            return null;
        }

        $query = DB::table('tours');

        if (Schema::hasColumn('tours', 'guide_id')) {
            $query->where('guide_id', $user->id);
        }

        $latest = $query->max('updated_at');

        return $latest ? (string) $latest : null;
    }

    private function upcomingTour($user): ?array
    {
        if (! $user || ! Schema::hasTable('tours')) {
            return null;
        }

        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->subMinutes(30)->format('H:i:s');

        $query = DB::table('tours');

        if (Schema::hasColumn('tours', 'guide_id')) {
            $query->where('guide_id', $user->id);
        } else {
            return null;
        }

        if (Schema::hasColumn('tours', 'status')) {
            $query->whereNotIn('status', ['completed', 'cancelled', 'canceled']);
        }

        if (Schema::hasColumn('tours', 'tour_date') && Schema::hasColumn('tours', 'start_time')) {
            $query->where(function ($q) use ($today, $nowTime) {
                $q->where('tour_date', '>', $today)
                    ->orWhere(function ($sameDay) use ($today, $nowTime) {
                        $sameDay->where('tour_date', $today)
                            ->where('start_time', '>=', $nowTime);
                    });
            })
            ->orderBy('tour_date')
            ->orderBy('start_time');
        } elseif (Schema::hasColumn('tours', 'start_at')) {
            $query->where('start_at', '>=', now()->subMinutes(30))
                ->orderBy('start_at');
        } else {
            $query->orderByDesc('updated_at');
        }

        $tour = $query->first();

        if (! $tour) {
            return null;
        }

        $title = $tour->title ?? ('Tur #' . $tour->id);
        $status = $tour->status ?? '';
        $date = $tour->tour_date ?? null;
        $time = $tour->start_time ?? null;

        if (! $date && isset($tour->start_at)) {
            $date = Carbon::parse($tour->start_at)->toDateString();
            $time = Carbon::parse($tour->start_at)->format('H:i:s');
        }

        $participants = $this->participantsForTour((int) $tour->id);

        return [
            'id' => (int) $tour->id,
            'title' => $title,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'date_label' => $date ? Carbon::parse($date)->format('Y-m-d') : '',
            'time_label' => $time ? substr((string) $time, 0, 5) : '',
            'participants' => $participants,
            'participants_label' => $participants . ' bokade',
            'show_url' => Route::has('guide.tours.show') ? route('guide.tours.show', $tour->id) : null,
            'start_url' => Route::has('guide.tours.start') ? route('guide.tours.start', $tour->id) : null,
            'complete_url' => Route::has('guide.tours.complete') ? route('guide.tours.complete', $tour->id) : null,
            'can_start' => in_array($status, ['', 'planned'], true) && Route::has('guide.tours.start'),
            'can_complete' => in_array($status, ['started', 'in_progress'], true) && Route::has('guide.tours.complete'),
            'updated_at' => $tour->updated_at ?? null,
        ];
    }

    private function participantsForTour(int $tourId): int
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasColumn('bookings', 'tour_id')) {
            return 0;
        }

        $query = DB::table('bookings')->where('tour_id', $tourId);

        if (Schema::hasColumn('bookings', 'status')) {
            $query->whereNotIn('status', ['cancelled', 'canceled']);
        }

        if (Schema::hasColumn('bookings', 'total_count')) {
            return (int) $query->sum('total_count');
        }

        $sum = 0;

        foreach (['men_count', 'women_count', 'youth_count', 'child_count'] as $column) {
            if (Schema::hasColumn('bookings', $column)) {
                $sum += (int) (clone $query)->sum($column);
            }
        }

        return $sum;
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'planned' => 'Planerad',
            'started', 'in_progress' => 'Pågår',
            'completed' => 'Avslutad',
            'cancelled', 'canceled' => 'Inställd',
            default => $status ?: 'Planerad',
        };
    }
}
