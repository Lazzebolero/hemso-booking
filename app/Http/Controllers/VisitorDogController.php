<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitorDogRequest;
use App\Http\Requests\UpdateVisitorDogRequest;
use App\Models\VisitorDog;
use App\Services\VisitorDogRegistrationService;
use App\Support\Roles;
use App\Support\VisitorDogActivityLogger;
use App\Support\VisitorDogSupport;
use App\Support\VisitorDogUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitorDogController extends Controller
{
    public function __construct(
        private VisitorDogRegistrationService $visitorDogRegistrations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VisitorDog::class);

        [$from, $to] = VisitorDogSupport::dateRangeFromRequest($request);

        $dogs = VisitorDog::query()
            ->where('registered_by', $request->user()->id)
            ->whereDate('visit_date', '>=', $from->toDateString())
            ->whereDate('visit_date', '<=', $to->toDateString())
            ->orderByDesc('visit_date')
            ->orderByDesc('tour_start_time')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $dogsNeedingPhoto = VisitorDog::query()
            ->with('registrar:id,name')
            ->where('registered_by', '!=', $request->user()->id)
            ->where(function ($query) {
                $query->whereNull('photo_path')->orWhere('photo_path', '');
            })
            ->whereDate('visit_date', '>=', $from->toDateString())
            ->whereDate('visit_date', '<=', $to->toDateString())
            ->orderByDesc('visit_date')
            ->orderByDesc('tour_start_time')
            ->orderBy('dog_name')
            ->get();

        return $this->viewForRole('visitor-dogs.mine-index', [
            'dogs' => $dogs,
            'dogsNeedingPhoto' => $dogsNeedingPhoto,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', VisitorDog::class);

        return $this->viewForRole('visitor-dogs.guide-form', [
            'defaultVisitDate' => now()->format('Y-m-d'),
        ]);
    }

    public function store(StoreVisitorDogRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $activeRole = session('active_role');
        if (! is_string($activeRole) || ! in_array($activeRole, [Roles::GUIDE, Roles::HOST], true)) {
            abort(403);
        }

        $dog = $this->visitorDogRegistrations->register(
            [
                'dog_name' => $validated['dog_name'],
                'breed' => $validated['breed'] ?? null,
                'owner_phone' => $validated['owner_phone'] ?? null,
                'visit_date' => $validated['visit_date'],
                'tour_start_time' => $validated['tour_start_time'] ?? null,
                'care_flags' => $validated['care_flags'] ?? null,
            ],
            $request->user(),
            $activeRole,
            $request->file('photo'),
        );

        return redirect()
            ->route('visitor-dogs.create')
            ->with('success', 'Hunden är registrerad.');
    }

    public function show(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorize('view', $visitorDog);

        return $this->viewForRole('visitor-dogs.show', [
            'dog' => $visitorDog,
            'backNav' => VisitorDogSupport::backNavigation($request),
            'navQuery' => VisitorDogSupport::preserveNavigationQuery($request),
            'photoCompletionOnly' => $request->user()?->can('completePhoto', $visitorDog) === true,
            'canDelete' => $request->user()?->can('delete', $visitorDog) === true,
        ]);
    }

    public function edit(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorize('update', $visitorDog);

        return $this->viewForRole('visitor-dogs.edit', [
            'dog' => $visitorDog,
            'backNav' => VisitorDogSupport::backNavigation($request),
            'navQuery' => VisitorDogSupport::preserveNavigationQuery($request),
            'photoCompletionOnly' => $request->user()?->can('completePhoto', $visitorDog) === true,
        ]);
    }

    public function update(UpdateVisitorDogRequest $request, VisitorDog $visitorDog): RedirectResponse
    {
        $this->authorize('update', $visitorDog);

        // Capture before apply: after photo save, completePhoto becomes false and show would 403.
        $photoCompletionOnly = $request->user()?->can('completePhoto', $visitorDog) === true;

        VisitorDogUpdater::apply($request, $visitorDog);

        if ($photoCompletionOnly) {
            return redirect()
                ->route('visitor-dogs.index')
                ->with('success', 'Bilden är sparad.');
        }

        return redirect()
            ->route('visitor-dogs.show', $visitorDog)
            ->with('success', 'Registreringen är uppdaterad.');
    }

    public function photo(Request $request, VisitorDog $visitorDog): BinaryFileResponse
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

        $backNav = VisitorDogSupport::backNavigation($request);

        return redirect()
            ->to($backNav['url'])
            ->with('success', 'Registreringen har tagits bort.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function viewForRole(string $viewName, array $data): View
    {
        $data['useGuideLayout'] = session('active_role') === Roles::GUIDE;

        return view($viewName, $data);
    }
}
