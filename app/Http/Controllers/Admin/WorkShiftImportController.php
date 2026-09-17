<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WorkShiftTemplateExport;
use App\Http\Controllers\Controller;
use App\Services\WorkShiftGridBuilder;
use App\Services\WorkShiftImportService;
use App\Services\WorkShiftStaffDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkShiftImportController extends Controller
{
    public function __construct(
        private WorkShiftStaffDirectory $directory,
        private WorkShiftImportService $import,
        private WorkShiftGridBuilder $builder,
    ) {}

    public function template(Request $request): BinaryFileResponse|RedirectResponse
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from')->toString())->startOfDay()
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to')->toString())->startOfDay()
            : now()->addMonths(2)->endOfMonth();

        if ($from->gt($to)) {
            return back()->withErrors([
                'from' => 'Till-datum måste vara samma dag eller senare än från-datum.',
            ]);
        }

        if ($from->diffInDays($to) > 150) {
            return back()->withErrors([
                'to' => 'Perioden får vara högst 150 dagar.',
            ]);
        }

        $filename = 'arbetsschema-mall-'.$from->toDateString().'-'.$to->toDateString().'.xlsx';

        return Excel::download(
            new WorkShiftTemplateExport($from, $to, $this->directory, $this->builder),
            $filename,
        );
    }

    public function create(): View
    {
        return view('admin.work-shifts.import', [
            'staffCount' => $this->directory->forTemplate()->count(),
            'templateFrom' => now()->startOfMonth()->toDateString(),
            'templateTo' => now()->addMonths(2)->endOfMonth()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse|View
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt'],
        ], [
            'file.required' => 'Välj en Excel-fil.',
            'file.mimes' => 'Filen måste vara Excel eller CSV.',
        ]);

        $path = $request->file('file')?->getRealPath();

        if (! is_string($path) || $path === '') {
            return back()->withErrors([
                'file' => 'Filen kunde inte läsas.',
            ]);
        }

        $preview = $this->import->previewUploaded($path);

        $request->session()->put('work_shift_import', [
            'ready' => $preview['ready'],
            'errors' => $preview['errors'],
            'skipped' => $preview['skipped'],
        ]);

        return view('admin.work-shifts.import-preview', [
            'ready' => $preview['ready'],
            'rowErrors' => $preview['errors'],
            'skipped' => $preview['skipped'],
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $payload = $request->session()->pull('work_shift_import');

        if (! is_array($payload) || ! isset($payload['ready']) || $payload['ready'] === []) {
            return redirect()
                ->route('admin.work-shifts.import')
                ->withErrors(['file' => 'Ingen import att bekräfta. Ladda upp filen igen.']);
        }

        $created = $this->import->commit($payload['ready'], $request->user());

        return redirect()
            ->route('admin.work-shifts.index')
            ->with('success', $created.' arbetspass importerades.');
    }
}
