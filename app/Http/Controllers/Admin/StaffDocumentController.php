<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffDocument;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffDocumentController extends Controller
{
    public function index(): View
    {
        $documents = StaffDocument::query()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.staff-documents.index', [
            'documents' => $documents,
            'audienceScopes' => StaffDocument::audienceScopes(),
            'restaurantFunctions' => WorkShift::restaurantFunctions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.staff-documents.create', [
            'document' => new StaffDocument([
                'audience_scope' => 'all',
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'audienceScopes' => StaffDocument::audienceScopes(),
            'restaurantFunctions' => WorkShift::restaurantFunctions(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        $file = $request->file('document_file');
        $data['file_path'] = $file->store('staff-documents', 'public');
        $data['original_name'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getClientMimeType();
        $data['uploaded_by'] = auth()->id();

        StaffDocument::create($data);

        return redirect()
            ->route('admin.staff-documents.index')
            ->with('success', 'Dokument uppladdat.');
    }

    public function edit(StaffDocument $staffDocument): View
    {
        return view('admin.staff-documents.edit', [
            'document' => $staffDocument,
            'audienceScopes' => StaffDocument::audienceScopes(),
            'restaurantFunctions' => WorkShift::restaurantFunctions(),
            'roleOptions' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, StaffDocument $staffDocument): RedirectResponse
    {
        $data = $this->validated($request, false);

        if ($request->hasFile('document_file')) {
            if ($staffDocument->file_path && Storage::disk('public')->exists($staffDocument->file_path)) {
                Storage::disk('public')->delete($staffDocument->file_path);
            }

            $file = $request->file('document_file');
            $data['file_path'] = $file->store('staff-documents', 'public');
            $data['original_name'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getClientMimeType();
        }

        $staffDocument->update($data);

        return redirect()
            ->route('admin.staff-documents.index')
            ->with('success', 'Dokument uppdaterat.');
    }

    public function destroy(StaffDocument $staffDocument): RedirectResponse
    {
        if ($staffDocument->file_path && Storage::disk('public')->exists($staffDocument->file_path)) {
            Storage::disk('public')->delete($staffDocument->file_path);
        }

        $staffDocument->delete();

        return redirect()
            ->route('admin.staff-documents.index')
            ->with('success', 'Dokument borttaget.');
    }

    private function validated(Request $request, bool $requireFile): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'audience_scope' => ['required', Rule::in(array_keys(StaffDocument::audienceScopes()))],
            'role_slug' => ['nullable', 'string', 'max:50'],
            'shift_function' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'document_file' => [$requireFile ? 'required' : 'nullable', 'file', 'max:20480'],
        ];

        $data = $request->validate($rules);

        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($data['audience_scope'] !== 'role') {
            $data['role_slug'] = null;
        }

        if ($data['audience_scope'] !== 'function') {
            $data['shift_function'] = null;
        }

        return $data;
    }

    private function roleOptions(): array
    {
        return [
            'guide' => 'Guide',
            'host' => 'Värd',
            'restaurant' => 'Restaurang',
            'admin' => 'Admin',
        ];
    }
}