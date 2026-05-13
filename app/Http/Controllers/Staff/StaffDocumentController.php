<?php

namespace App\Http\Controllers\Staff;

use App\Models\StaffDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffDocumentController extends StaffBaseController
{
    public function index(): View
    {
        $this->authorizeStaffAccess();

        $user = auth()->user();

        $documents = StaffDocument::query()
            ->active()
            ->visibleTo($user)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('staff.documents.index', [
            'documents' => $documents,
            'audienceScopes' => StaffDocument::audienceScopes(),
        ]);
    }

    public function show(StaffDocument $staffDocument): View
    {
        $this->authorizeStaffAccess();
        $this->ensureVisible($staffDocument);

        return view('staff.documents.show', [
            'document' => $staffDocument,
        ]);
    }

    public function preview(StaffDocument $staffDocument): BinaryFileResponse
    {
        $this->authorizeStaffAccess();
        $this->ensureVisible($staffDocument);

        abort_unless(
            $staffDocument->file_path && Storage::disk('public')->exists($staffDocument->file_path),
            404
        );

        $absolutePath = Storage::disk('public')->path($staffDocument->file_path);

        return response()->file($absolutePath, [
            'Content-Type' => $staffDocument->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $staffDocument->original_name . '"',
        ]);
    }

    public function download(StaffDocument $staffDocument): StreamedResponse
    {
        $this->authorizeStaffAccess();
        $this->ensureVisible($staffDocument);

        abort_unless(
            $staffDocument->file_path && Storage::disk('public')->exists($staffDocument->file_path),
            404
        );

        return Storage::disk('public')->download(
            $staffDocument->file_path,
            $staffDocument->original_name
        );
    }

    private function ensureVisible(StaffDocument $staffDocument): void
    {
        $visible = StaffDocument::query()
            ->whereKey($staffDocument->id)
            ->active()
            ->visibleTo(auth()->user())
            ->exists();

        abort_unless($visible, 403);
    }
}