<?php

namespace App\Events;

use App\Models\OpeningDeviation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OpeningDeviationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public OpeningDeviation $deviation) {}
}
