<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorDog;
use App\Support\Roles;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitorDogController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->visitorDogDateRangeFromRequest($request);

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

    public function gallery(Request $request): View
    {
        [$from, $to] = $this->visitorDogDateRangeFromRequest($request);

        $prefix = $this->visitorDogsRoutePrefix();

        $dogs = VisitorDog::query()
            ->with('registrar:id,name')
            ->whereNotNull('photo_path')
            ->where('photo_path', '!=', '')
            ->whereDate('visit_date', '>=', $from->toDateString())
            ->whereDate('visit_date', '<=', $to->toDateString())
            ->orderByDesc('visit_date')
            ->orderByDesc('tour_start_time')
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        $lightboxItems = $dogs->getCollection()
            ->map(static function (VisitorDog $dog) use ($prefix): array {
                return [
                    'photo' => route($prefix.'.visitor-dogs.photo', $dog),
                    'show' => route($prefix.'.visitor-dogs.show', $dog),
                    'name' => $dog->dog_name,
                    'date' => $dog->visit_date?->format('Y-m-d') ?? '',
                    'breed' => $dog->breed ?? '',
                ];
            })
            ->values()
            ->all();

        return view('admin.visitor-dogs.gallery', [
            'dogs' => $dogs,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'visitorDogsRoutePrefix' => $prefix,
            'lightboxItems' => Js::from($lightboxItems),
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

    /**
     * Strömma bild från disk (samma idé som felrapport-bilaga — fungerar utan public/storage-symlink).
     */
    public function photo(VisitorDog $visitorDog): BinaryFileResponse
    {
        if (empty($visitorDog->photo_path)) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($visitorDog->photo_path)) {
            abort(404);
        }

        $absolutePath = Storage::disk('public')->path($visitorDog->photo_path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath);
    }

    public function destroy(VisitorDog $visitorDog): RedirectResponse
    {
        $prefix = $this->visitorDogsRoutePrefix();

        if ($visitorDog->photo_path !== null && $visitorDog->photo_path !== '' && Storage::disk('public')->exists($visitorDog->photo_path)) {
            Storage::disk('public')->delete($visitorDog->photo_path);
        }

        $visitorDog->delete();

        $query = array_filter([
            'from_date' => request()->input('from_date'),
            'to_date' => request()->input('to_date'),
        ], static fn ($v): bool => is_string($v) && $v !== '');

        return redirect()
            ->route($prefix.'.visitor-dogs.index', $query)
            ->with('success', 'Registreringen har tagits bort.');
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function visitorDogDateRangeFromRequest(Request $request): array
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

        return [$from, $to];
    }

    private function visitorDogsRoutePrefix(): string
    {
        return session('active_role') === Roles::HOST ? 'host' : 'admin';
    }
}
