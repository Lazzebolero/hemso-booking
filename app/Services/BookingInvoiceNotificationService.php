<?php

namespace App\Services;

use App\Mail\BookingInvoiceRequestMail;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BookingInvoiceNotificationService
{
    public function notifyAfterStore(Booking $booking): ?string
    {
        if (! $booking->to_be_invoiced) {
            return null;
        }

        $email = trim((string) setting('economics_notification_email', ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $booking->loadMissing(['tour.tourType', 'languages']);

        try {
            Mail::to($email)->send(new BookingInvoiceRequestMail($booking));
        } catch (Throwable $exception) {
            report($exception);

            Log::warning('booking_invoice_notification_failed', [
                'booking_id' => $booking->id,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return 'Bokningen sparades men e-post till ekonomi kunde inte skickas.';
        }

        return null;
    }
}
