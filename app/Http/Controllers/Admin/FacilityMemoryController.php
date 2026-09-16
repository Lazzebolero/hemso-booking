<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityMemory;
use App\Models\ReportLocation;
use App\Models\Tour;
use App\Services\FacilityMemoryAccessService;
use App\Services\FacilityMemoryStoreService;
use App\Services\LogService;
use App\Support\ActiveRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacilityMemoryController extends Controller
{
    public function __construct(
        private FacilityMemoryStoreService $memoryStore,
        private FacilityMemoryAccessService $access,
    ) {}

    public function index(Request $request): View
    {
        if ($this->access->canViewArchive()) {
            return $this->archiveIndex($request);
        }

        $this->access->abortUnlessCanCollect();

        $memories = $this->access->scopeOwnMemories(
            FacilityMemory::query()
                ->with('tour.tourType')
                ->orderByDesc('created_at'),
            auth()->user(),
        )->paginate(20);

        return view('facility-memories.own-index', [
            'memories' => $memories,
            'routePrefix' => ActiveRole::routePrefix(),
            'shell' => ActiveRole::isHost() ? 'staff' : 'guide',
            'layout' => ActiveRole::isHost() ? 'layouts.app' : 'layouts.guide',
        ]);
    }

    public function create(Request $request): View
    {
        $this->access->abortUnlessCanCollect();
        abort_unless(ActiveRole::isHost(), 403);

        $tour = null;

        if ($request->filled('tour')) {
            $tour = Tour::query()
                ->with('tourType')
                ->find($request->integer('tour'));
        }

        $locations = ReportLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tourOptions = Tour::query()
            ->with('tourType')
            ->whereDate('tour_date', '>=', now()->subDays(7)->toDateString())
            ->whereNotIn('status', ['cancelled', 'canceled', 'completed'])
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->limit(60)
            ->get();

        return view('facility-memories.own-create', [
            'tour' => $tour,
            'locations' => $locations,
            'tourOptions' => $tourOptions,
            'routePrefix' => ActiveRole::routePrefix(),
            'shell' => 'staff',
            'layout' => 'layouts.app',
            'cardClass' => 'page-card mb-3',
            'showTourPicker' => true,
            'allowAudioFileUpload' => true,
            'backUrl' => route('host.memories.index'),
            'backLabel' => 'Mina minnen',
            'cancelUrl' => route('host.memories.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->access->abortUnlessCanCollect();
        abort_unless(ActiveRole::isHost(), 403);

        $validated = $this->memoryStore->validate($request);

        $memory = $this->memoryStore->store(
            $validated,
            $request,
            (int) $request->user()->id,
            restrictTourToGuide: false,
        );

        return redirect()
            ->route('host.memories.show', $memory)
            ->with('success', 'Minnet är sparat och skickat till arkivet.');
    }

    public function show(FacilityMemory $facilityMemory): View
    {
        $this->access->abortUnlessCanView($facilityMemory);

        if ($this->access->canViewArchive()) {
            if (
                $this->access->canAdministrateMemories()
                && $facilityMemory->status === FacilityMemory::STATUS_SUBMITTED
            ) {
                $facilityMemory->update([
                    'status' => FacilityMemory::STATUS_READ,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
            }

            $facilityMemory->load([
                'collectedBy:id,name',
                'reviewedBy:id,name',
                'tour.tourType',
            ]);

            return view('admin.facility-memories.show', [
                'memory' => $facilityMemory->fresh(),
                'statusOptions' => FacilityMemory::statusOptions(),
                'routePrefix' => $this->routePrefix(),
                'canAdministrate' => $this->access->canAdministrateMemories(),
                'canDelete' => $this->access->canDeleteMemories(),
            ]);
        }

        $facilityMemory->load(['tour.tourType']);

        return view('facility-memories.own-show', [
            'memory' => $facilityMemory,
            'routePrefix' => ActiveRole::routePrefix(),
            'shell' => ActiveRole::isHost() ? 'staff' : 'guide',
            'layout' => ActiveRole::isHost() ? 'layouts.app' : 'layouts.guide',
        ]);
    }

    public function update(Request $request, FacilityMemory $facilityMemory): RedirectResponse
    {
        $this->access->abortUnlessCanAdministrate();
        $this->access->abortUnlessCanView($facilityMemory);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(FacilityMemory::statusOptions()))],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'rejection_reason' => [
                Rule::requiredIf($request->input('status') === FacilityMemory::STATUS_REJECTED),
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'rejection_reason.required' => 'Ange en anledning när minnet avvisas.',
        ]);

        $facilityMemory->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'rejection_reason' => $validated['status'] === FacilityMemory::STATUS_REJECTED
                ? ($validated['rejection_reason'] ?? null)
                : null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.facility-memories.show', $facilityMemory)
            ->with('success', 'Minnet uppdaterades.');
    }

    public function destroy(FacilityMemory $facilityMemory): RedirectResponse
    {
        $this->access->abortUnlessCanDelete();
        $this->access->abortUnlessCanView($facilityMemory);

        $memoryId = $facilityMemory->id;

        LogService::log(
            'facility_memory',
            $memoryId,
            'deleted',
            $facilityMemory->toArray(),
            null,
            'Raderade anläggningsminne från arkivet'
        );

        if ($facilityMemory->audio_path) {
            Storage::disk('public')->delete($facilityMemory->audio_path);
        }

        $facilityMemory->delete();

        return redirect()
            ->route('admin.facility-memories.index')
            ->with('success', 'Minnet raderades permanent.');
    }

    public function audio(FacilityMemory $facilityMemory): StreamedResponse
    {
        $this->access->abortUnlessCanView($facilityMemory);

        abort_unless($facilityMemory->isAudio() && $facilityMemory->audio_path, 404);

        abort_unless(Storage::disk('public')->exists($facilityMemory->audio_path), 404);

        return Storage::disk('public')->response(
            $facilityMemory->audio_path,
            'facility-memory-'.$facilityMemory->id.'.'.pathinfo($facilityMemory->audio_path, PATHINFO_EXTENSION),
            [
                'Content-Type' => $facilityMemory->audio_mime_type ?: 'audio/webm',
            ]
        );
    }

    protected function archiveIndex(Request $request): View
    {
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $search = trim($request->string('q')->toString());

        $memories = $this->access->scopeArchiveIndex(
            FacilityMemory::query()
                ->with([
                    'collectedBy:id,name',
                    'tour.tourType',
                ])
        )
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($type !== '' && $type !== 'all', fn ($query) => $query->where('type', $type))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('body', 'like', '%'.$search.'%')
                        ->orWhere('context_note', 'like', '%'.$search.'%')
                        ->orWhere('location_text', 'like', '%'.$search.'%')
                        ->orWhere('era_text', 'like', '%'.$search.'%')
                        ->orWhere('visitor_name', 'like', '%'.$search.'%')
                        ->orWhereHas('collectedBy', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $newCount = FacilityMemory::query()
            ->where('status', FacilityMemory::STATUS_SUBMITTED)
            ->count();

        return view('admin.facility-memories.index', [
            'memories' => $memories,
            'status' => $status !== '' ? $status : 'all',
            'type' => $type !== '' ? $type : 'all',
            'search' => $search,
            'newCount' => $newCount,
            'statusOptions' => FacilityMemory::statusOptions(),
            'routePrefix' => $this->routePrefix(),
            'canAdministrate' => $this->access->canAdministrateMemories(),
            'canDelete' => $this->access->canDeleteMemories(),
        ]);
    }

    protected function routePrefix(): string
    {
        return ActiveRole::facilityMemoriesRoutePrefix() ?? 'admin';
    }
}
