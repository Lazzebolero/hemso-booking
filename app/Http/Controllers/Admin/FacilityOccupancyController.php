<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FacilityOccupancyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FacilityOccupancyController extends Controller
{
    public function updateExtra(Request $request, FacilityOccupancyService $occupancy): RedirectResponse
    {
        $data = $request->validate([
            'extra_count' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $occupancy->updateExtraCount((int) $data['extra_count'], (int) auth()->id());

        return back()->with('success', 'Övrigt antal i anläggningen uppdaterat.');
    }
}
