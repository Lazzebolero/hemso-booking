<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function sendBookingConfirmation(Booking $booking): void
{
    if (!$booking->email) {
        return;
    }

    $this->sendTemplate(
        key: 'booking_confirmation',
        recipientEmail: $booking->email,
        data: $this->bookingPayload($booking),
        languageCode: 'sv',
        notifiable: $booking
    );
    }

    public function sendBookingUpdated(Booking $booking): void
{
    if (!$booking->email) {
        return;
    }

    $this->sendTemplate(
        key: 'booking_updated',
        recipientEmail: $booking->email,
        data: $this->bookingPayload($booking),
        languageCode: 'sv',
        notifiable: $booking
    );
}

    public function sendBookingCancelled(Booking $booking): void
{
    if (!$booking->email) {
        return;
    }

    $this->sendTemplate(
        key: 'booking_cancelled',
        recipientEmail: $booking->email,
        data: $this->bookingPayload($booking),
        languageCode: 'sv',
        notifiable: $booking
    );
}

    public function sendGuideAssigned(Tour $tour): void
    {
        $tour->loadMissing('guide', 'tourType');

        if (!$tour->guide?->email) {
            return;
        }

        $this->sendTemplate(
            key: 'guide_assigned',
            recipientEmail: $tour->guide->email,
            data: $this->tourPayload($tour, $tour->guide)
        );
    }

    public function sendGuideTourUpdated(Tour $tour): void
    {
        $tour->loadMissing('guide', 'tourType');

        if (!$tour->guide?->email) {
            return;
        }

        $this->sendTemplate(
            key: 'guide_tour_updated',
            recipientEmail: $tour->guide->email,
            data: $this->tourPayload($tour, $tour->guide)
        );
    }
	public function sendBookingReminder(Booking $booking): void
{
    if (!$booking->email) {
        return;
    }

    $this->sendTemplate(
        key: 'booking_reminder',
        recipientEmail: $booking->email,
        data: $this->bookingPayload($booking),
        languageCode: 'sv',
        notifiable: $booking
    );
}

    public function sendTemplate(
    string $key,
    string $recipientEmail,
    array $data,
    string $languageCode = 'sv',
    $notifiable = null
): void
    {
        $template = NotificationTemplate::query()
            ->where('template_key', $key)
            ->where('channel', 'mail')
            ->where('language_code', $languageCode)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $subject = $this->replaceVariables($template->subject, $data);
        $bodyHtml = $this->replaceVariables($template->body_html, $data);

        $log = NotificationLog::create([
    'notifiable_type' => $notifiable ? get_class($notifiable) : null,
    'notifiable_id' => $notifiable?->id,
    'template_key' => $key,
    'recipient_email' => $recipientEmail,
    'subject' => $subject,
    'payload' => $data,
    'status' => 'queued',
]);

        try {
            Mail::to($recipientEmail)->send(new TemplatedMail($subject, $bodyHtml));

            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function replaceVariables(string $text, array $data): string
    {
        foreach ($data as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }

        return $text;
    }

    private function bookingPayload(Booking $booking): array
    {
        $booking->loadMissing(['tour.guide', 'tour.tourType', 'languages']);

        return [
            'booking_name' => $booking->booking_name ?? '',
            'contact_name' => $booking->contact_name ?? '',
            'phone' => $booking->phone ?? '',
            'email' => $booking->email ?? '',
            'total_count' => $booking->total_count ?? 0,
            'status' => $booking->status ?? '',
            'arrival_status' => $booking->arrival_status ?? '',
            'tour_title' => $booking->tour?->title ?? '',
            'tour_date' => optional($booking->tour?->tour_date)?->format('Y-m-d') ?? '',
            'start_time' => $booking->tour?->start_time ?? '',
            'end_time' => $booking->tour?->end_time ?? '',
            'guide_name' => $booking->tour?->guide?->name ?? '',
            'tour_type' => $booking->tour?->tourType?->name ?? '',
            'languages' => $booking->languages->pluck('name')->implode(', '),
        ];
    }

    private function tourPayload(Tour $tour, ?User $guide = null): array
    {
        return [
            'tour_title' => $tour->title ?? '',
            'tour_date' => optional($tour->tour_date)?->format('Y-m-d') ?? '',
            'start_time' => $tour->start_time ?? '',
            'end_time' => $tour->end_time ?? '',
            'status' => $tour->status ?? '',
            'guide_name' => $guide?->name ?? '',
            'guide_email' => $guide?->email ?? '',
            'tour_type' => $tour->tourType?->name ?? '',
        ];
    }
}