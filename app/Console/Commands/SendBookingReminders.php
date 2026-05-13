<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Skicka påminnelsemail för kommande bokningar';

    public function handle(NotificationService $notificationService): int
    {
        $from = now();
        $to = now()->addDay();

        $bookings = Booking::with(['tour.guide', 'tour.tourType', 'languages'])
            ->whereNotNull('email')
            ->whereNull('reminder_sent_at')
            ->where('status', '!=', 'cancelled')
            ->where('is_waitlist', false)
            ->whereHas('tour', function ($query) use ($from, $to) {
                $query->where('status', 'planned')
                    ->where(function ($q) use ($from, $to) {
                        $q->whereDate('tour_date', $from->toDateString())
                          ->orWhereDate('tour_date', $to->toDateString());
                    });
            })
            ->get()
            ->filter(function ($booking) use ($from, $to) {
                if (!$booking->tour || !$booking->tour->tour_date || !$booking->tour->start_time) {
                    return false;
                }

                $tourStart = \Carbon\Carbon::parse(
                    $booking->tour->tour_date->format('Y-m-d') . ' ' . $booking->tour->start_time
                );

                return $tourStart->between($from, $to);
            });

        $count = 0;

       foreach ($bookings as $booking) {
    try {
        $notificationService->sendBookingReminder($booking);

        $booking->update([
            'reminder_sent_at' => now(),
        ]);

        $count++;
    } catch (\Throwable $e) {
        \Log::error('Reminder mail failed', [
            'booking_id' => $booking->id,
            'error' => $e->getMessage(),
        ]);
    }
}

        $this->info("Klart. {$count} påminnelsemail skickades.");

        return self::SUCCESS;
    }
}
