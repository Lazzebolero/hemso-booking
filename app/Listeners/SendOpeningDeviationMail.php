<?php

namespace App\Listeners;

use App\Events\OpeningDeviationCreated;
use App\Mail\OpeningDeviationMail;
use App\Services\OpeningDeviationNotificationService;
use Illuminate\Support\Facades\Mail;

class SendOpeningDeviationMail
{
    public function __construct(
        private OpeningDeviationNotificationService $notifications,
    ) {}

    public function handle(OpeningDeviationCreated $event): void
    {
        $deviation = $event->deviation->loadMissing([
            'openingCheck.openedBy',
            'reporter',
        ]);

        foreach ($this->notifications->recipientEmails() as $email) {
            Mail::to($email)->send(new OpeningDeviationMail($deviation));
        }
    }
}
