<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class CameraTestController extends Controller
{
    public function create(): View
    {
        return view('guide.camera-test');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(4096),
            ],
        ], [
            'photo.required' => 'Ta en testbild först.',
            'photo.max' => 'Testbilden får vara högst 4 MB.',
        ]);

        $uploaded = $request->file('photo');
        if (! $uploaded instanceof UploadedFile || ! $uploaded->isValid()) {
            return back()->withErrors(['photo' => 'Bilden kunde inte läsas.'])->withInput();
        }

        $uploaded->store('camera_tests/'.now()->format('Y/m'), 'public');

        return back()->with('success', 'Testbilden togs emot.');
    }
}
