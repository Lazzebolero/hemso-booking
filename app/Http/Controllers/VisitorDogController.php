<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVisitorDogRequest;
use App\Models\VisitorDog;
use App\Support\Roles;
use App\Support\VisitorDogUpdater;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            ->where('registered_by', $request->user()->id)
            ->whereDate('visit_date', '>=', $from->toDateString())
            ->whereDate('visit_date', '<=', $to->toDateString())
            ->orderByDesc('visit_date')
            ->orderByDesc('tour_start_time')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return $this->viewForRole('visitor-dogs.mine-index', [
            'dogs' => $dogs,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
        ]);
    }

    public function create(): View
    {
        return $this->viewForRole(
            session('active_role') === Roles::GUIDE
                ? 'visitor-dogs.guide-form'
                : 'visitor-dogs.host-form',
            ['defaultVisitDate' => now()->format('Y-m-d')]
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dog_name' => ['required', 'string', 'max:120'],
            'breed' => ['nullable', 'string', 'max:120'],
            'owner_phone' => ['nullable', 'string', 'max:40'],
            'visit_date' => ['required', 'date'],
            'tour_start_time' => ['nullable', 'date_format:H:i'],
            'photo' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif'])
                    ->max(10240),
            ],
        ], [
            'dog_name.required' => 'Ange hundens namn.',
            'visit_date.required' => 'Ange datum.',
            'photo.max' => 'Bilden får vara högst 10 MB.',
        ]);

        $photoPath = null;
        $uploaded = $request->file('photo');
        if ($uploaded instanceof UploadedFile && $uploaded->isValid()) {
            $stored = $uploaded->store(
                'visitor_dogs/'.now()->format('Y/m'),
                'public'
            );
            $photoPath = $stored !== false ? $stored : null;
        }

        $activeRole = session('active_role');
        if (! is_string($activeRole) || ! in_array($activeRole, [Roles::GUIDE, Roles::HOST], true)) {
            abort(403);
        }

        VisitorDog::query()->create([
            'dog_name' => $validated['dog_name'],
            'breed' => $validated['breed'] ?? null,
            'owner_phone' => $validated['owner_phone'] ?? null,
            'visit_date' => $validated['visit_date'],
            'tour_start_time' => $validated['tour_start_time'] ?? null,
            'photo_path' => $photoPath,
            'registered_by' => $request->user()->id,
            'registered_as_role' => $activeRole,
        ]);

        return redirect()
            ->route('visitor-dogs.create')
            ->with('success', 'Hunden är registrerad.');
    }

    public function show(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorizeOwnRegistration($request, $visitorDog);

        return $this->viewForRole('visitor-dogs.show', [
            'dog' => $visitorDog,
        ]);
    }

    public function edit(Request $request, VisitorDog $visitorDog): View
    {
        $this->authorizeOwnRegistration($request, $visitorDog);

        return $this->viewForRole('visitor-dogs.edit', [
            'dog' => $visitorDog,
        ]);
    }

    public function update(UpdateVisitorDogRequest $request, VisitorDog $visitorDog): RedirectResponse
    {
        $this->authorizeOwnRegistration($request, $visitorDog);

        VisitorDogUpdater::apply($request, $visitorDog);

        return redirect()
            ->route('visitor-dogs.show', $visitorDog)
            ->with('success', 'Registreringen är uppdaterad.');
    }

    public function photo(Request $request, VisitorDog $visitorDog): BinaryFileResponse
    {
        $this->authorizeOwnRegistration($request, $visitorDog);

        return $this->streamPhoto($visitorDog);
    }

    public function destroy(Request $request, VisitorDog $visitorDog): RedirectResponse
    {
        $this->authorizeOwnRegistration($request, $visitorDog);

        VisitorDogUpdater::deletePhotoFile($visitorDog);
        $visitorDog->delete();

        $query = array_filter([
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ], static fn ($v): bool => is_string($v) && $v !== '');

        return redirect()
            ->route('visitor-dogs.index', $query)
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

    private function authorizeOwnRegistration(Request $request, VisitorDog $visitorDog): void
    {
        if ($visitorDog->registered_by !== $request->user()->id) {
            abort(403);
        }
    }

    private function streamPhoto(VisitorDog $visitorDog): BinaryFileResponse
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
}
