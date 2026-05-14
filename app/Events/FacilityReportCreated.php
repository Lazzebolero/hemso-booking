<?php

namespace App\Events;

use App\Models\FacilityReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FacilityReportCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public FacilityReport $report) {}
}
