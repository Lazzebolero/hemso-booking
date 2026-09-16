<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\FacilityMemory;
use App\Models\ReportLocation;
use App\Models\Tour;
use App\Services\FacilityMemoryAccessService;
use App\Services\FacilityMemoryStoreService;
use App\Support\ActiveRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacilityMemoryController extends Controller
{
    public function __construct(
        private FacilityMemoryStoreService $memoryStore,
        private FacilityMemoryAccessService $access,
    ) {}

    public function index(): View
    {
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
            'shell' => $this->shell(),
            'layout' => $this->layout(),
        ]);
    }

    public function show(FacilityMemory $facilityMemory): View
    {
        $this->access->abortUnlessCanView($facilityMemory);

        $facilityMemory->load(['tour.tourType']);

        return view('facility-memories.own-show', [
            'memory' => $facilityMemory,
            'routePrefix' => ActiveRole::routePrefix(),
            'shell' => $this->shell(),
            'layout' => $this->layout(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->access->abortUnlessCanCollect();

        $tour = null;

        if ($request->filled('tour')) {
            $tour = Tour::query()->find($request->integer('tour'));

            if ($tour !== null && ActiveRole::isGuide()) {
                $this->ensureGuideOwnsTour($tour);
            }
        }

        $locations = ReportLocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $tourOptions = collect();

        if (ActiveRole::isHost()) {
            $tourOptions = Tour::query()
                ->with('tourType')
                ->whereDate('tour_date', '>=', now()->subDays(7)->toDateString())
                ->whereNotIn('status', ['cancelled', 'canceled', 'completed'])
                ->orderBy('tour_date')
                ->orderBy('start_time')
                ->limit(60)
                ->get();
        }

        return view('facility-memories.own-create', [
            'tour' => $tour,
            'locations' => $locations,
            'tourOptions' => $tourOptions,
            'routePrefix' => ActiveRole::routePrefix(),
            'shell' => $this->shell(),
            'layout' => $this->layout(),
            'cardClass' => ActiveRole::isHost() ? 'page-card mb-3' : 'guide-card mb-3',
            'showTourPicker' => ActiveRole::isHost(),
            'allowAudioFileUpload' => ActiveRole::isHost(),
            'backUrl' => ActiveRole::isHost()
                ? route('host.memories.index')
                : ($tour ? route('guide.tours.show', $tour) : route('guide.dashboard')),
            'backLabel' => ActiveRole::isHost() ? 'Mina minnen' : 'Tillbaka',
            'cancelUrl' => ActiveRole::isHost()
                ? route('host.memories.index')
                : ($tour ? route('guide.tours.show', $tour) : route('guide.dashboard')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->access->abortUnlessCanCollect();

        $validated = $this->memoryStore->validate($request);

        $memory = $this->memoryStore->store(
            $validated,
            $request,
            (int) $request->user()->id,
            restrictTourToGuide: ActiveRole::isGuide(),
        );

        return redirect()
            ->route(ActiveRole::routePrefix().'.memories.show', $memory)
            ->with('success', 'Minnet är sparat och skickat till arkivet.');
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

    protected function ensureGuideOwnsTour(Tour $tour): void
    {
        if ((int) $tour->guide_id !== (int) auth()->id()) {
            abort(403);
        }
    }

    protected function shell(): string
    {
        return ActiveRole::isHost() ? 'staff' : 'guide';
    }

    protected function layout(): string
    {
        return ActiveRole::isHost() ? 'layouts.app' : 'layouts.guide';
    }
}
