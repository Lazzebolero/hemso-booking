<?php

namespace App\Http\Controllers\Host;

use App\Http\Controllers\Controller;
use App\Support\GuideShell;
use Illuminate\View\View;

class HostEntryController extends Controller
{
    public function __invoke(): View
    {
        GuideShell::clear();

        return view('host.entry');
    }
}
