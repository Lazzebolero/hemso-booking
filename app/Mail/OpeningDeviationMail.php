<?php

namespace App\Mail;

use App\Models\OpeningDeviation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OpeningDeviationMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public OpeningDeviation $deviation) {}

    public function build(): self
    {
        $this->deviation->loadMissing(['openingCheck.openedBy', 'reporter']);

        return $this
            ->subject('Avvikelse vid öppningskontroll: '.$this->deviation->checkpointLabel())
            ->view('emails.opening-deviation')
            ->with([
                'deviation' => $this->deviation,
                'showUrl' => route('admin.opening-checks.show', $this->deviation->openingCheck, absolute: true),
            ]);
    }
}
