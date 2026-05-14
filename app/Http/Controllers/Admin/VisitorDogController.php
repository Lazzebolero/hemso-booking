<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorDog;
use App\Support\Roles;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VisitorDogController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $from = $request->filled('from_date')
            ? Carbon::parse($request->string('from_date'))->startOfDay()
            : now()->startOfDay();

        $to = $request->filled('to_date')
            ? Carbon::parse($request->string('to_date'))->startOfDay()
            : now()->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $dogs = VisitorDog::query()
            ->with('registrar:id,name')
            ->whereDate('visit_date', '>=', $from->toDateString())
            ->whereDate('visit_date', '<=', $to->toDateString())
            ->orderByDesc('visit_date')
            ->orderByDesc('tour_start_time')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.visitor-dogs.index', [
            'dogs' => $dogs,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'visitorDogsRoutePrefix' => $this->visitorDogsRoutePrefix(),
        ]);
    }

    public function show(VisitorDog $visitorDog): View
    {
        $visitorDog->load('registrar:id,name,email');

        return view('admin.visitor-dogs.show', [
            'dog' => $visitorDog,
            'visitorDogsRoutePrefix' => $this->visitorDogsRoutePrefix(),
        ]);
    }

    private function visitorDogsRoutePrefix(): string
    {
        return session('active_role') === Roles::HOST ? 'host' : 'admin';
    }
}
