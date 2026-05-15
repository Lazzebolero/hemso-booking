<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVisitorDogRequest;
use App\Models\ActivityLog;
use App\Models\VisitorDog;
use App\Support\ActiveRole;
use App\Support\VisitorDogActivityLogger;
use App\Support\VisitorDogSupport;
use App\Support\VisitorDogUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitorDogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', VisitorDog::class);

        [$from, $to] = VisitorDogSupport::dateRangeFromRequest($request);

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
        ]);
    }

    public function gallery(Request $request): View
    {
        $this->authorize('viewAny', VisitorDog::class);

        [$from, $to] = VisitorDogSupport::dateRangeFromRequest($request);

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

        return view('admin.visitor-dogs.gallery', [
            'dogs' => $dogs,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
        ]);
    }

    public function show(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorize('view', $visitorDog);

        $visitorDog->load('registrar:id,name,email');

        $activityLogs = ActivityLog::query()
            ->with('user:id,name')
            ->where('entity_type', VisitorDogActivityLogger::ENTITY_TYPE)
            ->where('entity_id', $visitorDog->id)
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.visitor-dogs.show', [
            'dog' => $visitorDog,
            'activityLogs' => $activityLogs,
            'backNav' => VisitorDogSupport::backNavigation($request, ActiveRole::visitorDogsRoutePrefix()),
            'navQuery' => VisitorDogSupport::preserveNavigationQuery($request),
        ]);
    }

    public function edit(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorize('update', $visitorDog);

        return view('admin.visitor-dogs.edit', [
            'dog' => $visitorDog,
            'backNav' => VisitorDogSupport::backNavigation($request, ActiveRole::visitorDogsRoutePrefix()),
            'navQuery' => VisitorDogSupport::preserveNavigationQuery($request),
        ]);
    }

    public function update(UpdateVisitorDogRequest $request, VisitorDog $visitorDog): RedirectResponse
    {
        $this->authorize('update', $visitorDog);

        VisitorDogUpdater::apply($request, $visitorDog);

        return redirect()
            ->route(
                ActiveRole::visitorDogsRoutePrefix().'.visitor-dogs.show',
                array_merge(['visitorDog' => $visitorDog], VisitorDogSupport::preserveNavigationQuery($request)),
            )
            ->with('success', 'Registreringen är uppdaterad.');
    }

    public function photo(VisitorDog $visitorDog): BinaryFileResponse
    {
        $this->authorize('view', $visitorDog);

        return VisitorDogSupport::streamPhoto($visitorDog);
    }

    public function destroy(Request $request, VisitorDog $visitorDog): RedirectResponse
    {
        $this->authorize('delete', $visitorDog);

        VisitorDogActivityLogger::logDeleted($visitorDog);
        VisitorDogUpdater::deletePhotoFile($visitorDog);
        $visitorDog->delete();

        $backNav = VisitorDogSupport::backNavigation($request, ActiveRole::visitorDogsRoutePrefix());

        return redirect()
            ->to($backNav['url'])
            ->with('success', 'Registreringen har tagits bort.');
    }
}
