<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sound;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AudioSoundController extends Controller
{
    public function index(): View
    {
        $sounds = Sound::query()
            ->withCount('loudspeakers')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.audio.sounds.index', compact('sounds'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'audio_file' => [
                'required',
                'file',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/ogg,application/ogg,audio/flac,audio/x-flac',
                'max:51200',
            ],
        ]);

        $file = $request->file('audio_file');
        $filePath = $file->store('audio', 'public');
        $publicPath = url(Storage::disk('public')->url($filePath));

        Sound::create([
            'name' => $data['name'],
            'path' => $publicPath,
            'file_path' => $filePath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ljudfil uppladdad.');
    }

    public function destroy(Sound $sound): RedirectResponse
    {
        if ($sound->loudspeakers()->exists()) {
            return back()->withErrors(['sound' => 'Ljudet används av minst en kanal och kan inte tas bort.']);
        }

        $sound->deleteStoredFile();
        $sound->delete();

        return back()->with('success', 'Ljudfil borttagen.');
    }
}
