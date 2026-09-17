<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WorkShiftTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\WorkShiftSpreadsheet;
use App\Services\WorkShiftImportService;
use App\Services\WorkShiftStaffDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkShiftImportController extends Controller
{
    public function __construct(
        private WorkShiftStaffDirectory $directory,
        private WorkShiftImportService $import,
    ) {}

    public function template(): BinaryFileResponse
    {
        $filename = 'arbetsschema-mall-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new WorkShiftTemplateExport($this->directory), $filename);
    }

    public function create(): View
    {
        return view('admin.work-shifts.import', [
            'staffCount' => $this->directory->forTemplate()->count(),
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

        $sheets = Excel::toCollection(new WorkShiftSpreadsheet, $request->file('file'));
        $rows = $sheets->first() ?? collect();

        if ($rows->isEmpty()) {
            return back()->withErrors([
                'file' => 'Filen innehåller inga rader i fliken Arbetspass.',
            ]);
        }

        $preview = $this->import->preview($rows);

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
