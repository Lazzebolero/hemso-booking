<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HostEntryController extends Controller
{
    public function __invoke(): View
    {
        return view('host.entry');
    }
}
