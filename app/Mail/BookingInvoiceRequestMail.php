<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingInvoiceRequestMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Booking $booking) {}

    public function build(): self
    {
        $this->booking->loadMissing(['tour.tourType', 'languages']);

        return $this
            ->subject('Bokning att fakturera: '.$this->booking->booking_name)
            ->view('emails.booking-invoice-request')
            ->with([
                'booking' => $this->booking,
                'editUrl' => route('admin.bookings.edit', $this->booking, absolute: true),
            ]);
    }
}
